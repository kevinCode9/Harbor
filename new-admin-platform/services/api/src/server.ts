import http from 'http';
import { Server } from 'socket.io';
import { createApp } from './app.js';
import { PORT } from './config/env.js';
import { initializeDatabase } from './config/database.js';
import { socketOptions } from './config/websocket.js';
import { ensureSeedAdminKey } from './modules/auth/auth.repository.js';
import { attachRealtimeGateway } from './realtime/gateway.js';

async function startServer() {
  const io = new Server(socketOptions);
  const { broadcastSessions } = attachRealtimeGateway(io);
  const app = createApp(io, broadcastSessions);
  const httpServer = http.createServer(app);
  io.attach(httpServer);

  await initializeDatabase();
  await ensureSeedAdminKey();

  httpServer.listen(PORT, '127.0.0.1', () => {
    console.log(`API + visitor pages  http://127.0.0.1:${PORT}`);
    console.log(`Health                http://127.0.0.1:${PORT}/api/health`);
  });
}

startServer().catch((error) => {
  console.error('Failed to initialize server:', error);
  process.exit(1);
});
