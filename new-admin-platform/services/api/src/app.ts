import express from 'express';
import cors from 'cors';
import helmet from 'helmet';
import morgan from 'morgan';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
import type { Express } from 'express';
import type { Server as SocketServer } from 'socket.io';
import { ALLOWED_PAGE_URLS, DEFAULT_PAGE } from './modules/pages/page.registry.js';
import { getVisitorRoot, visitorFileForUrl } from './modules/pages/page.service.js';
import { errorHandler } from './middleware/error-handler.js';
import { requestId } from './middleware/request-id.js';
import { createApiRouter } from './routes/index.js';

const here = path.dirname(fileURLToPath(import.meta.url));
const adminDist = path.resolve(here, '../../../dist-admin');

export function createApp(io: SocketServer, broadcastSessions: () => Promise<void>): Express {
  const app = express();
  const visitorRoot = getVisitorRoot();
  const adminBuilt = fs.existsSync(path.join(adminDist, 'index.html'));

  app.use((req, res, next) => {
    res.setHeader('X-Robots-Tag', 'noindex, nofollow, noarchive');
    next();
  });
  app.use(
    helmet({
      contentSecurityPolicy: false,
      crossOriginOpenerPolicy: false,
      crossOriginResourcePolicy: process.env.NODE_ENV === 'production' ? { policy: 'same-origin' } : false,
      frameguard: process.env.NODE_ENV === 'production' ? { action: 'sameorigin' } : false,
    }),
  );
  app.use(cors({ origin: true, credentials: true }));
  app.use(express.json({ limit: '32kb' }));
  app.use(morgan('dev'));
  app.use(requestId);

  app.get('/robots.txt', (_req, res) => {
    res.type('text/plain').send('User-agent: *\nDisallow: /\n');
  });

  app.use('/api', createApiRouter(io, broadcastSessions));

  // Built React admin (npm run build:admin) — same host as API + visitor pages.
  if (adminBuilt) {
    app.use('/admin', express.static(adminDist, { index: false }));
    app.get(/^\/admin(?:\/.*)?$/, (_req, res) => {
      res.sendFile(path.join(adminDist, 'index.html'));
    });
  }

  app.use(express.static(visitorRoot));
  app.use('/public', express.static(path.join(visitorRoot, 'public')));

  for (const url of ALLOWED_PAGE_URLS) {
    app.get(url, (_req, res) => {
      res.sendFile(visitorFileForUrl(url));
    });
  }

  app.get('/', (_req, res) => {
    if (adminBuilt) {
      res.redirect('/admin');
      return;
    }
    res.redirect(DEFAULT_PAGE);
  });

  app.use((req, res, next) => {
    if (req.method !== 'GET' && req.method !== 'HEAD') return next();
    if (req.path.startsWith('/api') || req.path.startsWith('/socket.io') || req.path.startsWith('/admin')) {
      return next();
    }
    if (path.extname(req.path)) return next();
    res.redirect(302, '/offline');
  });

  app.use(errorHandler);
  return app;
}
