import { Router } from "express";
import { z } from "zod";
import { CASE_STATUSES, CLIENT_SCREENS } from "@harbor/shared";
import type { Server as IoServer } from "socket.io";
import { db, type CaseRow } from "../db/client.js";
import { looksLikeSecretMaterial } from "../lib/crypto.js";
import { writeAudit } from "../lib/audit.js";
import { serializeCase } from "../lib/serialize.js";
import { requireAgent } from "../middleware/auth.js";
import { cuidLike, nowIso } from "../lib/ids.js";

function getCase(id: string): CaseRow | undefined {
  return db.prepare("SELECT * FROM support_cases WHERE id = ?").get(id) as CaseRow | undefined;
}

export function agentRouter(io: IoServer): Router {
  const router = Router();
  router.use(requireAgent);

  router.get("/cases", async (_req, res, next) => {
    try {
      const rows = db
        .prepare("SELECT * FROM support_cases ORDER BY updated_at DESC LIMIT 200")
        .all() as CaseRow[];
      res.json({ cases: rows.map(serializeCase) });
    } catch (err) {
      next(err);
    }
  });

  router.get("/cases/:id", async (req, res, next) => {
    try {
      const row = getCase(req.params.id);
      if (!row) {
        res.status(404).json({ error: "Case not found" });
        return;
      }
      const messages = db
        .prepare(
          "SELECT id, case_id, author_role, body, created_at FROM messages WHERE case_id = ? ORDER BY created_at ASC",
        )
        .all(row.id) as Array<{
        id: string;
        case_id: string;
        author_role: string;
        body: string;
        created_at: string;
      }>;
      const notes = db
        .prepare(
          `SELECT n.id, n.body, n.created_at, a.display_name AS agent_name
           FROM internal_notes n
           JOIN agents a ON a.id = n.agent_id
           WHERE n.case_id = ?
           ORDER BY n.created_at DESC`,
        )
        .all(row.id) as Array<{
        id: string;
        body: string;
        created_at: string;
        agent_name: string;
      }>;
      const audits = db
        .prepare(
          `SELECT id, type, summary, created_at FROM audit_events
           WHERE case_id = ? ORDER BY created_at DESC LIMIT 100`,
        )
        .all(row.id) as Array<{
        id: string;
        type: string;
        summary: string;
        created_at: string;
      }>;
      res.json({
        case: serializeCase(row),
        messages: messages.map((m) => ({
          id: m.id,
          caseId: m.case_id,
          authorRole: m.author_role,
          body: m.body,
          createdAt: m.created_at,
        })),
        notes: notes.map((n) => ({
          id: n.id,
          body: n.body,
          createdAt: n.created_at,
          agentName: n.agent_name,
        })),
        audits: audits.map((a) => ({
          id: a.id,
          type: a.type,
          summary: a.summary,
          createdAt: a.created_at,
        })),
      });
    } catch (err) {
      next(err);
    }
  });

  router.patch("/cases/:id/status", async (req, res, next) => {
    try {
      const parsed = z.object({ status: z.enum(CASE_STATUSES) }).safeParse(req.body);
      if (!parsed.success) {
        res.status(400).json({ error: "Invalid status" });
        return;
      }
      if (!getCase(req.params.id)) {
        res.status(404).json({ error: "Case not found" });
        return;
      }
      db.prepare("UPDATE support_cases SET status = ?, updated_at = ? WHERE id = ?").run(
        parsed.data.status,
        nowIso(),
        req.params.id,
      );
      const updated = getCase(req.params.id)!;
      writeAudit({
        type: "STATUS_CHANGED",
        summary: `Status set to ${parsed.data.status}`,
        caseId: updated.id,
        agentId: req.agent!.id,
        meta: { status: parsed.data.status },
      });
      const dto = serializeCase(updated);
      io.to(`case:${updated.id}`).emit("case:updated", dto);
      io.to("agents").emit("case:updated", dto);
      res.json({ case: dto });
    } catch (err) {
      next(err);
    }
  });

  router.post("/cases/:id/screen", async (req, res, next) => {
    try {
      const parsed = z.object({ screen: z.enum(CLIENT_SCREENS) }).safeParse(req.body);
      if (!parsed.success) {
        res.status(400).json({ error: "Invalid screen" });
        return;
      }
      if (!getCase(req.params.id)) {
        res.status(404).json({ error: "Case not found" });
        return;
      }
      db.prepare(
        "UPDATE support_cases SET current_screen = ?, updated_at = ? WHERE id = ?",
      ).run(parsed.data.screen, nowIso(), req.params.id);
      const updated = getCase(req.params.id)!;
      writeAudit({
        type: "SCREEN_CHANGED",
        summary: `Agent set screen to ${parsed.data.screen}`,
        caseId: updated.id,
        agentId: req.agent!.id,
        meta: { screen: parsed.data.screen, by: "agent" },
      });
      const dto = serializeCase(updated);
      io.to(`case:${updated.id}`).emit("case:updated", dto);
      io.to("agents").emit("case:updated", dto);
      res.json({ case: dto });
    } catch (err) {
      next(err);
    }
  });

  router.post("/cases/:id/messages", async (req, res, next) => {
    try {
      const parsed = z.object({ body: z.string().trim().min(1).max(2000) }).safeParse(req.body);
      if (!parsed.success) {
        res.status(400).json({ error: "Invalid message" });
        return;
      }
      if (looksLikeSecretMaterial(parsed.data.body)) {
        res.status(400).json({
          error: "Message rejected: do not transmit authentication secrets.",
        });
        return;
      }
      if (!getCase(req.params.id)) {
        res.status(404).json({ error: "Case not found" });
        return;
      }
      const id = cuidLike();
      const createdAt = nowIso();
      db.prepare(
        `INSERT INTO messages (id, case_id, author_role, agent_id, body, created_at)
         VALUES (?, ?, 'AGENT', ?, ?, ?)`,
      ).run(id, req.params.id, req.agent!.id, parsed.data.body, createdAt);
      db.prepare("UPDATE support_cases SET updated_at = ? WHERE id = ?").run(
        createdAt,
        req.params.id,
      );
      writeAudit({
        type: "MESSAGE_SENT",
        summary: "Agent sent a support message",
        caseId: req.params.id,
        agentId: req.agent!.id,
      });
      const payload = {
        id,
        caseId: req.params.id,
        authorRole: "AGENT" as const,
        body: parsed.data.body,
        createdAt,
      };
      io.to(`case:${payload.caseId}`).emit("message:new", payload);
      io.to("agents").emit("message:new", payload);
      res.status(201).json({ message: payload });
    } catch (err) {
      next(err);
    }
  });

  router.post("/cases/:id/notes", async (req, res, next) => {
    try {
      const parsed = z.object({ body: z.string().trim().min(1).max(4000) }).safeParse(req.body);
      if (!parsed.success) {
        res.status(400).json({ error: "Invalid note" });
        return;
      }
      if (looksLikeSecretMaterial(parsed.data.body)) {
        res.status(400).json({
          error: "Note rejected: do not store authentication secrets.",
        });
        return;
      }
      if (!getCase(req.params.id)) {
        res.status(404).json({ error: "Case not found" });
        return;
      }
      const id = cuidLike();
      const createdAt = nowIso();
      db.prepare(
        `INSERT INTO internal_notes (id, case_id, agent_id, body, created_at)
         VALUES (?, ?, ?, ?, ?)`,
      ).run(id, req.params.id, req.agent!.id, parsed.data.body, createdAt);
      writeAudit({
        type: "NOTE_ADDED",
        summary: "Agent added an internal note",
        caseId: req.params.id,
        agentId: req.agent!.id,
      });
      res.status(201).json({
        note: {
          id,
          body: parsed.data.body,
          createdAt,
          agentName: req.agent!.displayName,
        },
      });
    } catch (err) {
      next(err);
    }
  });

  return router;
}
