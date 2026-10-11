import bcrypt from "bcryptjs";
import { randomBytes } from "node:crypto";
import { db } from "./client.js";
import "./migrate.js";

const email = (process.env.AGENT_BOOTSTRAP_EMAIL || "agent@harbor.local").toLowerCase();
const password = process.env.AGENT_BOOTSTRAP_PASSWORD || "ChangeMeOnFirstLogin!";
const passwordHash = bcrypt.hashSync(password, 12);
const now = new Date().toISOString();
const id = randomBytes(12).toString("hex");

const existing = db.prepare("SELECT id FROM agents WHERE email = ?").get(email) as
  | { id: string }
  | undefined;

if (existing) {
  db.prepare(
    "UPDATE agents SET password_hash = ?, display_name = ? WHERE id = ?",
  ).run(passwordHash, "Harbor Agent", existing.id);
} else {
  db.prepare(
    `INSERT INTO agents (id, email, password_hash, display_name, created_at)
     VALUES (?, ?, ?, ?, ?)`,
  ).run(id, email, passwordHash, "Harbor Agent", now);
}

console.log(`Bootstrap agent ready: ${email}`);
console.log("Change the bootstrap password after first login.");
