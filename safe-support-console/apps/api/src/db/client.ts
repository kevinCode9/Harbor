import { DatabaseSync } from "node:sqlite";
import fs from "node:fs";
import path from "node:path";
import { fileURLToPath } from "node:url";

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const dataDir = path.resolve(__dirname, "../../data");
const dbPath = process.env.DATABASE_PATH || path.join(dataDir, "harbor.db");

fs.mkdirSync(path.dirname(dbPath), { recursive: true });

export const db = new DatabaseSync(dbPath);
db.exec("PRAGMA journal_mode = WAL;");
db.exec("PRAGMA foreign_keys = ON;");

export type CaseRow = {
  id: string;
  case_number: string;
  status: string;
  current_screen: string;
  contact_name: string | null;
  contact_email: string | null;
  public_address: string | null;
  public_address_chain: string | null;
  customer_token_hash: string;
  created_at: string;
  updated_at: string;
};

export type AgentRow = {
  id: string;
  email: string;
  password_hash: string;
  display_name: string;
  created_at: string;
};
