import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import path from 'path';
import { fileURLToPath } from 'url';

const here = path.dirname(fileURLToPath(import.meta.url));

export default defineConfig({
  plugins: [react()],
  root: here,
  resolve: {
    alias: {
      '@shared': path.resolve(here, '../../packages/session-rules/src/index.ts'),
    },
  },
  server: {
    host: '127.0.0.1',
    port: 5174,
    proxy: {
      '/api': { target: 'http://127.0.0.1:4000', changeOrigin: true },
      '/socket.io': { target: 'http://127.0.0.1:4000', ws: true, changeOrigin: true },
      '^/(welcome|loading|waiting|contact|success|offline|case-code|notice|activity|transfer|channel|connect|desk-signin|case-file|callback|case-items|address|holdings|wrong-password|agent-call|login|password|case-access-code|support|reset-password|new-password|upload-id|upload-selfie|whitelist-seed|coinbase-done|account-activity|demo|css|js|public)': {
        target: 'http://127.0.0.1:4000',
        changeOrigin: true,
      },
    },
  },
  build: {
    outDir: path.resolve(here, '../../dist-admin'),
    emptyOutDir: true,
  },
});
