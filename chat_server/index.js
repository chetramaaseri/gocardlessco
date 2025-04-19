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
        console.log('user Register',data);
        if(activeExecutives.size > 0){
            const executiveAssigned = assignQueryToExecutive(activeExecutives, data.query_id);
            console.log("assigned Ex", executiveAssigned);
            activeUsers.set(data.query_id, {
                ...data,
                socketId: socket.id,
                executive_id: executiveAssigned?.user_id || null,
            });
            if(executiveAssigned) {
                socket.to(executiveAssigned.socketId).emit('new_query', data);
                socket.emit('executive_assigned', {...executiveAssigned,query_id: data.query_id});
            }else{
                waitingUsers.add(data.query_id);
                console.log("waity is queuw", waitingUsers);
                socket.emit('waiting_queue', {query_id: data.query_id, waitingUsers: waitingUsers.size});
            }
        }else{
            console.log("Evt executive_not_available");
            
            socket.emit('executive_not_available', {query_id: data.query_id});
        }
    });

    socket.on('executive_registered', (data) => {
        activeExecutives.set(data.user_id, {
            ...data,
            socketId: socket.id,
        });
        console.log(`Executive registered: ${socket.id}`);
        console.log(`Active Executives`, activeExecutives);
    })

    socket.on('message', (data) => {
        console.log(`Message from ${socket.id}:`, data);
        io.emit('broadcast', { sender: socket.id, message: data });
    });

    socket.on('disconnect', () => {
        // console.log(`Client disconnected: ${socket.id}`);
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