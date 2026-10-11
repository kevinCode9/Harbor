import path from 'path';
import { fileURLToPath } from 'url';
import dotenv from 'dotenv';

const platformRoot = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../../..');
dotenv.config({ path: path.join(process.cwd(), '.env'), override: true });
dotenv.config({ path: path.join(platformRoot, '.env'), override: false });

export const PORT = Number(process.env.PORT || 4000);
export const ADMIN_KEY = process.env.ADMIN_KEY || 'demo-admin-key';
export const DATABASE_URL =
  process.env.DATABASE_URL || 'postgresql://postgres:postgres@127.0.0.1:5432/live_admin';
export const PLATFORM_ROOT = platformRoot;
