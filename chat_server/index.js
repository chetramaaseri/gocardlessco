import 'dotenv/config';
import path from 'path';
import { fileURLToPath } from 'url';
import express from 'express';
import http from 'http';
import { Server } from 'socket.io';

// Configure __dirname equivalent for ES modules
const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

// Load .env from parent directory
import dotenv from 'dotenv';
// import { assignQueryToExecutive } from './functions.js';
import Redis from 'ioredis';
dotenv.config({ path: path.resolve(__dirname, '../.env') });

// Initialize Express and HTTP server
const app = express();
const server = http.createServer(app);
const io = new Server(server, {
    cors: {
        origin: process.env.CORS_ORIGIN || '*',
        methods: ['GET', 'POST']
    }
});

const redis = new Redis({
    host: process.env.REDIS_SERVER_HOST | 'localhost',
    port: process.env.REDIS_SERVER_PORT | 6379,
    db: 0 
});

async function storeChat(queryId, message) {
    try {
      let chatHistory = await redis.get(queryId);
      if (chatHistory) {
        chatHistory = JSON.parse(chatHistory);
      } else {
        chatHistory = [];
      }
      chatHistory.push(message);
      await redis.set(queryId, JSON.stringify(chatHistory));
      console.log(`Chat message added for queryId ${queryId}`);
    } catch (err) {
      console.error('Error storing chat:', err);
    }
}

async function getMultipleQueryData(queryIds) {
    const result = {};
    for (const queryId of queryIds) {
      const data = await redis.get(queryId);
      result[queryId] = JSON.parse(data) ?? [];
    }
    return result;
}
  
async function getChats(queryId) {
    try {
      const chats = await redis.get(queryId);
      if (chats) {
        return JSON.parse(chats);
      } else {
        console.log(`No chats found for queryId ${queryId}`);
        return [];
      }
    } catch (err) {
      console.error('Error retrieving chats:', err);
    }
}

// Middleware
app.use(express.json());

// Store active connections
const activeConnections = new Map();
const activeUsers = new Map();
const activeExecutives = new Map();
const waitingUsers = new Set();

// Get config from environment
const PORT = process.env.CHAT_SERVER_PORT || 3000;
const HOST = process.env.CHAT_SERVER_HOST || '0.0.0.0';

// Socket.IO Logic
io.on('connection', (socket) => {
    //   console.log(`New client connected: ${socket.id}`);
    activeConnections.set(socket.id, socket);

    socket.on('user_registered', async (data) => {
        console.log('user Register',data);
        // console.log("data log type, " ,typeof data);
        const chats = await getChats(data.query_id);
        if(chats){
            socket.emit('chatHistory', chats);
        }
        if(data.executive_id){
            const executiveAssigned = activeExecutives.get(data.executive_id.toString());
            console.log("old ex assigned", executiveAssigned);
            console.log("all exe", activeExecutives);
            
            if(executiveAssigned) {
                activeUsers.set(data.query_id, {
                    ...data,
                    socketId: socket.id,
                    executive_id: executiveAssigned?.user_id || null,
                });
                socket.emit('executive_assigned', {query_id: data.query_id, ...executiveAssigned});
                socket.to(executiveAssigned.socketId).emit('new_query', {...data, chatHistory : chats});
            }
            return;
        }
        if(activeExecutives.size > 0){
            let executiveAssigned;
            // const executiveAssigned = assignQueryToExecutive(activeExecutives, data.query_id);
            const sortedExecutives = Array.from(activeExecutives.entries()).sort(([, a], [, b]) => Number(a.preference) - Number(b.preference));
            for (const [key, exec] of sortedExecutives) {
                if (exec.assignedQueries.some(existingQuery => existingQuery.queryId === data.queryId)) {
                    executiveAssigned = exec;
                }else if(exec.totalAssigned < exec.capacity) {
                    exec.assignedQueries.push(data);
                    exec.totalAssigned += 1;
                    activeExecutives.set(key, exec);
                    executiveAssigned = exec;
                }
            }
            // console.log("assigned Ex", executiveAssigned);
            activeUsers.set(data.query_id, {
                ...data,
                socketId: socket.id,
                executive_id: executiveAssigned?.user_id || null,
            });
            if(executiveAssigned) {
                // console.log("exe assign data", {query_id: data.query_id, ...executiveAssigned});
                
                socket.emit('executive_assigned', {query_id: data.query_id, ...executiveAssigned});
                socket.to(executiveAssigned.socketId).emit('new_query', data);
            }else{
                waitingUsers.add(data.query_id);
                socket.emit('waiting_queue', {query_id: data.query_id, waitingUsers: waitingUsers.size});
            }
        }else{
            socket.emit('executive_not_available', {query_id: data.query_id});
        }
    });

    socket.on('executive_registered', async (data) => {
        activeExecutives.set(data.user_id, {
            ...data,
            socketId: socket.id,
        });
        const chatHistory = await getMultipleQueryData(data.assignedQueries);
        socket.emit('chatHistory', chatHistory);
        console.log(`Executive registered: ${socket.id}`);
        // console.log(`Active Executives`, activeExecutives);
    })

    socket.on('executiveMessage', async (data) => {
        console.log(`Message from ${socket.id}:`, data);
        const assignedUser = activeUsers.get(data.query_id);
        if(assignedUser){
            await storeChat(data.query_id, data);
            io.to(assignedUser.socketId).emit('executiveMessage', data);
        }

        // io.emit('broadcast', { sender: socket.id, message: data });
    });

    socket.on('userMessage', async (data) => {
        console.log(`Message from ${socket.id}:`, data);
        const executiveAssigned = activeExecutives.get(data.executive_id);
        console.log(executiveAssigned);
        await storeChat(data.query_id, data);
        io.to(executiveAssigned.socketId).emit('userMessage', data);
        // io.emit('broadcast', { sender: socket.id, message: data });
    });

    socket.on('disconnect', async () => {
        // console.log(`Client disconnected: ${socket.id}`);
        for (const [queryId, user] of activeUsers.entries()) {
            if (user.socketId === socket.id) {
                activeUsers.delete(queryId);
                const chats = await getChats(queryId);
                console.log("this user chats is ", chats);
                
                console.log(`Removed user with queryId ${queryId} on disconnect`);
                break; // Stop after finding one
            }
        }
        for (const [userId, user] of activeExecutives.entries()) {
            if (user.socketId === socket.id) {
                // console.log("removing executive", user);
                console.log("all assigned executives", user.assignedQueries);
                
                // save chats for all query ids in executive 
                activeExecutives.delete(userId);
                // console.log(`Removed Executive with userId ${userId} on disconnect`);
                break; // Stop after finding one
            }
        }
        // console.log("on disconnect active execuives",activeExecutives);
        // console.log("on disconnect active users",activeUsers);
        
        activeConnections.delete(socket.id);
        // console.log(`Remaining connections: ${activeConnections.size}`);
    });
});

// REST API Endpoints
app.get('/api/connections', (req, res) => {
    res.json({
        count: activeConnections.size,
        connections: Array.from(activeConnections.keys())
    });
});

app.post('/api/send-message', (req, res) => {
    const { socketId, message } = req.body;

    if (!activeConnections.has(socketId)) {
        return res.status(404).json({ error: 'Socket not found' });
    }

    activeConnections.get(socketId).emit('admin-message', {
        from: 'server',
        message,
        timestamp: new Date().toISOString()
    });

    res.json({ success: true });
});

app.post('/api/broadcast', (req, res) => {
    const { message } = req.body;
    io.emit('broadcast', {
        sender: 'SERVER',
        message,
        timestamp: new Date().toISOString()
    });
    res.json({ success: true });
});

// Start server
server.listen(PORT, HOST, () => {
    console.log(`Server running on http://${HOST}:${PORT}`);
    console.log(`CORS origin: ${process.env.CORS_ORIGIN || '*'}`);
});