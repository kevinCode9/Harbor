import { db } from "./client.js";

db.exec(`
CREATE TABLE IF NOT EXISTS agents (
  id TEXT PRIMARY KEY,
  email TEXT NOT NULL UNIQUE,
  password_hash TEXT NOT NULL,
  display_name TEXT NOT NULL,
  created_at TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS agent_sessions (
  id TEXT PRIMARY KEY,
  agent_id TEXT NOT NULL REFERENCES agents(id) ON DELETE CASCADE,
  token_hash TEXT NOT NULL UNIQUE,
  expires_at TEXT NOT NULL,
  created_at TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS support_cases (
  id TEXT PRIMARY KEY,
  case_number TEXT NOT NULL UNIQUE,
  status TEXT NOT NULL DEFAULT 'OPEN',
  current_screen TEXT NOT NULL DEFAULT 'WELCOME',
  contact_name TEXT,
  contact_email TEXT,
  public_address TEXT,
  public_address_chain TEXT,
  customer_token_hash TEXT NOT NULL UNIQUE,
  created_at TEXT NOT NULL,
  updated_at TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS messages (
  id TEXT PRIMARY KEY,
  case_id TEXT NOT NULL REFERENCES support_cases(id) ON DELETE CASCADE,
  author_role TEXT NOT NULL,
  agent_id TEXT REFERENCES agents(id),
  body TEXT NOT NULL,
  created_at TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS internal_notes (
  id TEXT PRIMARY KEY,
  case_id TEXT NOT NULL REFERENCES support_cases(id) ON DELETE CASCADE,
  agent_id TEXT NOT NULL REFERENCES agents(id),
  body TEXT NOT NULL,
  created_at TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS audit_events (
  id TEXT PRIMARY KEY,
  case_id TEXT REFERENCES support_cases(id) ON DELETE SET NULL,
  agent_id TEXT REFERENCES agents(id),
  type TEXT NOT NULL,
  summary TEXT NOT NULL,
  meta_json TEXT,
  created_at TEXT NOT NULL
);
`);

console.log("Harbor SQLite schema ready.");
