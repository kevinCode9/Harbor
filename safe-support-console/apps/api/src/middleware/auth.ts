import type { NextFunction, Request, Response } from "express";
import { db, type AgentRow } from "../db/client.js";
import { hashToken } from "../lib/crypto.js";

export type AgentContext = {
  id: string;
  email: string;
  displayName: string;
};

declare global {
  namespace Express {
    interface Request {
      agent?: AgentContext;
      customerCaseId?: string;
    }
  }
}

const COOKIE = "harbor_agent";

export function agentCookieName(): string {
  return COOKIE;
}

export async function requireAgent(
  req: Request,
  res: Response,
  next: NextFunction,
): Promise<void> {
  try {
    const raw = req.cookies?.[COOKIE];
    if (!raw || typeof raw !== "string") {
      res.status(401).json({ error: "Authentication required" });
      return;
    }
    const tokenHash = hashToken(raw);
    const row = db
      .prepare(
        `SELECT a.id, a.email, a.display_name, s.expires_at
         FROM agent_sessions s
         JOIN agents a ON a.id = s.agent_id
         WHERE s.token_hash = ?`,
      )
      .get(tokenHash) as
      | { id: string; email: string; display_name: string; expires_at: string }
      | undefined;
    if (!row || new Date(row.expires_at) < new Date()) {
      res.status(401).json({ error: "Session expired" });
      return;
    }
    req.agent = {
      id: row.id,
      email: row.email,
      displayName: row.display_name,
    };
    next();
  } catch (err) {
    next(err);
  }
}

export async function requireCustomerCase(
  req: Request,
  res: Response,
  next: NextFunction,
): Promise<void> {
  try {
    const raw =
      (req.headers["x-case-token"] as string | undefined) ||
      (typeof req.query.token === "string" ? req.query.token : undefined);
    if (!raw) {
      res.status(401).json({ error: "Case token required" });
      return;
    }
    const tokenHash = hashToken(raw);
    const supportCase = db
      .prepare("SELECT id FROM support_cases WHERE customer_token_hash = ?")
      .get(tokenHash) as { id: string } | undefined;
    if (!supportCase) {
      res.status(401).json({ error: "Invalid case token" });
      return;
    }
    req.customerCaseId = supportCase.id;
    next();
  } catch (err) {
    next(err);
  }
}

export function findAgentByEmail(email: string): AgentRow | undefined {
  return db.prepare("SELECT * FROM agents WHERE email = ?").get(email) as AgentRow | undefined;
}
