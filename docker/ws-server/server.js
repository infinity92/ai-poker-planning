const WebSocket = require('ws');
const { createClient } = require('redis');

async function startServer() {
    const wss = new WebSocket.Server({ port: 8080, host: '0.0.0.0' });

    const subscriber = createClient({ url: 'redis://redis:6379' });
    subscriber.on('error', (err) => console.error('Redis Subscriber Error', err));
    await subscriber.connect();

    console.log('Redis subscriber connected.');

    wss.on('connection', (ws) => {
        console.log('Client connected');
        ws.on('message', (message) => {
            try {
                const data = JSON.parse(message);
                if (data.type === 'subscribe' && data.roomId) {
                    ws.roomId = data.roomId;
                    console.log(`Client subscribed to room ${ws.roomId}`);
                }
            } catch (e) {
                console.error('Failed to parse message or invalid message format', message);
            }
        });
        ws.on('close', () => {
            console.log('Client disconnected');
        });
    });

    await subscriber.pSubscribe('room:*', (message, channel) => {
        console.log(`Message from Redis on channel ${channel}`);
        const roomId = channel.split(':')[1];

        wss.clients.forEach((client) => {
            if (client.roomId === roomId && client.readyState === WebSocket.OPEN) {
                client.send(message);
            }
        });
    });

    console.log('WebSocket server started and subscribed to Redis channels.');
}

startServer().catch(console.error);

