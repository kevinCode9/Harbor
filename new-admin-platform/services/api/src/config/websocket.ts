import type { ServerOptions } from 'socket.io';

export const socketOptions: Partial<ServerOptions> = {
  cors: {
    origin: true,
    methods: ['GET', 'POST'],
    credentials: true,
  },
};
