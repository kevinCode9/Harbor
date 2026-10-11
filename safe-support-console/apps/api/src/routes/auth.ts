import { Router } from "express";
import bcrypt from "bcryptjs";
import { z } from "zod";
import { generateToken, hashToken } from "../lib/crypto.js";
import { writeAudit } from "../lib/audit.js";
import { cuidLike, nowIso } from "../lib/ids.js";
import { agentCookieName, findAgentByEmail, requireAgent } from "../middleware/auth.js";
import { db } from "../db/client.js";

export const authRouter = Router();

const loginSchema = z.object({
  email: z.string().email().max(200),
  password: z.string().min(8).max(200),
});

authRouter.post("/login", async (req, res, next) => {
  try {
    const parsed = loginSchema.safeParse(req.body);
    if (!parsed.success) {
      res.status(400).json({ error: "Invalid credentials payload" });
      return;
    }
    const agent = findAgentByEmail(parsed.data.email.toLowerCase());
    if (!agent) {
      res.status(401).json({ error: "Invalid email or password" });
      return;
    }
    const ok = await bcrypt.compare(parsed.data.password, agent.password_hash);
    if (!ok) {
      res.status(401).json({ error: "Invalid email or password" });
      return;
    }
    const token = generateToken();
    const expiresAt = new Date(Date.now() + 1000 * 60 * 60 * 12).toISOString();
    db.prepare(
      `INSERT INTO agent_sessions (id, agent_id, token_hash, expires_at, created_at)
       VALUES (?, ?, ?, ?, ?)`,
    ).run(cuidLike(), agent.id, hashToken(token), expiresAt, nowIso());
    writeAudit({
      type: "AGENT_LOGIN",
      summary: `Agent ${agent.email} signed in`,
      agentId: agent.id,
    });
    res.cookie(agentCookieName(), token, {
      httpOnly: true,
      sameSite: "lax",
      secure: process.env.COOKIE_SECURE === "true",
      maxAge: 1000 * 60 * 60 * 12,
      path: "/",
    });
    res.json({
      agent: {
        id: agent.id,
        email: agent.email,
        displayName: agent.display_name,
      },
    });
  } catch (err) {
    next(err);
  }
});

authRouter.post("/logout", requireAgent, async (req, res, next) => {
  try {
    const raw = req.cookies?.[agentCookieName()];
    if (raw) {
      db.prepare("DELETE FROM agent_sessions WHERE token_hash = ?").run(hashToken(raw));
    }
    res.clearCookie(agentCookieName(), { path: "/" });
    res.json({ ok: true });
  } catch (err) {
    next(err);
  }
});

authRouter.get("/me", requireAgent, async (req, res) => {
  res.json({ agent: req.agent });
});
