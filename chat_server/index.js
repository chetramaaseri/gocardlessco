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
import { assignQueryToExecutive } from './functions.js';
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

    socket.on('user_registered', (data) => {
        // console.log('user Register',data);
        // console.log("data log type, " ,typeof data);
        
        if(activeExecutives.size > 0){
            const executiveAssigned = assignQueryToExecutive(activeExecutives, data.query_id);
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

    socket.on('executive_registered', (data) => {
        activeExecutives.set(data.user_id, {
            ...data,
            socketId: socket.id,
        });
        console.log(`Executive registered: ${socket.id}`);
        // console.log(`Active Executives`, activeExecutives);
    })

    socket.on('executiveMessage', (data) => {
        console.log(`Message from ${socket.id}:`, data);
        const assignedUser = activeUsers.get(data.query_id);
        console.log(assignedUser);
        io.to(assignedUser.socketId).emit('executiveMessage', data);

        // io.emit('broadcast', { sender: socket.id, message: data });
    });

    socket.on('userMessage', (data) => {
        console.log(`Message from ${socket.id}:`, data);
        const executiveAssigned = activeExecutives.get(data.executive_id);
        console.log(executiveAssigned);
        io.to(executiveAssigned.socketId).emit('userMessage', data);
        // io.emit('broadcast', { sender: socket.id, message: data });
    });

    socket.on('disconnect', () => {
        // console.log(`Client disconnected: ${socket.id}`);
        for (const [queryId, user] of activeUsers.entries()) {
            if (user.socketId === socket.id) {
                activeUsers.delete(queryId);
                console.log(`Removed user with queryId ${queryId} on disconnect`);
                break; // Stop after finding one
            }
        }
        for (const [userId, user] of activeExecutives.entries()) {
            if (user.socketId === socket.id) {
                activeExecutives.delete(userId);
                console.log(`Removed Executive with userId ${userId} on disconnect`);
                break; // Stop after finding one
            }
        }
        console.log("on disconnect active execuives",activeExecutives);
        console.log("on disconnect active users",activeUsers);
        
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