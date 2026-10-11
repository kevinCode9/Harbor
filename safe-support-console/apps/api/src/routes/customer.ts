import { Router } from "express";
import { z } from "zod";
import { CLIENT_SCREENS } from "@harbor/shared";
import type { Server as IoServer } from "socket.io";
import { db, type CaseRow } from "../db/client.js";
import {
  generateCaseNumber,
  generateToken,
  hashToken,
  isPlausiblePublicAddress,
  looksLikeSecretMaterial,
} from "../lib/crypto.js";
import { writeAudit } from "../lib/audit.js";
import { serializeCase } from "../lib/serialize.js";
import { requireCustomerCase } from "../middleware/auth.js";
import { cuidLike, nowIso } from "../lib/ids.js";

function getCase(id: string): CaseRow {
  return db.prepare("SELECT * FROM support_cases WHERE id = ?").get(id) as CaseRow;
}

export function customerRouter(io: IoServer): Router {
  const router = Router();

  router.post("/cases", async (_req, res, next) => {
    try {
      const customerToken = generateToken();
      let caseNumber = generateCaseNumber();
      for (let i = 0; i < 5; i++) {
        const exists = db
          .prepare("SELECT id FROM support_cases WHERE case_number = ?")
          .get(caseNumber);
        if (!exists) break;
        caseNumber = generateCaseNumber();
      }
      const id = cuidLike();
      const ts = nowIso();
      db.prepare(
        `INSERT INTO support_cases
         (id, case_number, status, current_screen, customer_token_hash, created_at, updated_at)
         VALUES (?, ?, 'OPEN', 'WELCOME', ?, ?, ?)`,
      ).run(id, caseNumber, hashToken(customerToken), ts, ts);
      const created = getCase(id);
      writeAudit({
        type: "CASE_CREATED",
        summary: `Case ${caseNumber} opened`,
        caseId: created.id,
      });
      io.to("agents").emit("case:created", serializeCase(created));
      res.status(201).json({
        case: serializeCase(created),
        customerToken,
      });
    } catch (err) {
      next(err);
    }
  });

  router.get("/cases/me", requireCustomerCase, async (req, res, next) => {
    try {
      res.json({ case: serializeCase(getCase(req.customerCaseId!)) });
    } catch (err) {
      next(err);
    }
  });

  router.post("/cases/me/screen", requireCustomerCase, async (req, res, next) => {
    try {
      const parsed = z.object({ screen: z.enum(CLIENT_SCREENS) }).safeParse(req.body);
      if (!parsed.success) {
        res.status(400).json({ error: "Invalid screen" });
        return;
      }
      db.prepare(
        "UPDATE support_cases SET current_screen = ?, updated_at = ? WHERE id = ?",
      ).run(parsed.data.screen, nowIso(), req.customerCaseId!);
      const updated = getCase(req.customerCaseId!);
      writeAudit({
        type: "SCREEN_CHANGED",
        summary: `Customer moved to ${parsed.data.screen}`,
        caseId: updated.id,
        meta: { screen: parsed.data.screen, by: "customer" },
      });
      const dto = serializeCase(updated);
      io.to(`case:${updated.id}`).emit("case:updated", dto);
      io.to("agents").emit("case:updated", dto);
      res.json({ case: dto });
    } catch (err) {
      next(err);
    }
  });

  router.post("/cases/me/contact", requireCustomerCase, async (req, res, next) => {
    try {
      const parsed = z
        .object({
          name: z.string().trim().max(120).optional().or(z.literal("")),
          email: z.string().trim().email().max(200).optional().or(z.literal("")),
        })
        .safeParse(req.body);
      if (!parsed.success) {
        res.status(400).json({ error: "Invalid contact fields" });
        return;
      }
      const name = parsed.data.name?.trim() || null;
      const email = parsed.data.email?.trim().toLowerCase() || null;
      if (name && looksLikeSecretMaterial(name)) {
        res.status(400).json({
          error:
            "That value looks like a secret. Do not enter passwords, codes, or recovery phrases.",
        });
        return;
      }
      db.prepare(
        "UPDATE support_cases SET contact_name = ?, contact_email = ?, updated_at = ? WHERE id = ?",
      ).run(name, email, nowIso(), req.customerCaseId!);
      const updated = getCase(req.customerCaseId!);
      writeAudit({
        type: "CONTACT_UPDATED",
        summary: "Customer updated optional contact details",
        caseId: updated.id,
      });
      const dto = serializeCase(updated);
      io.to(`case:${updated.id}`).emit("case:updated", dto);
      io.to("agents").emit("case:updated", dto);
      res.json({ case: dto });
    } catch (err) {
      next(err);
    }
  });

  router.post("/cases/me/public-address", requireCustomerCase, async (req, res, next) => {
    try {
      const parsed = z
        .object({
          chainLabel: z.string().trim().min(2).max(40),
          address: z.string().trim().min(26).max(90),
        })
        .safeParse(req.body);
      if (!parsed.success) {
        res.status(400).json({ error: "Invalid public address payload" });
        return;
      }
      if (!isPlausiblePublicAddress(parsed.data.address)) {
        res.status(400).json({
          error:
            "Address rejected. Provide a public address only — never a seed phrase or private key.",
        });
        return;
      }
      db.prepare(
        `UPDATE support_cases
         SET public_address = ?, public_address_chain = ?, updated_at = ?
         WHERE id = ?`,
      ).run(
        parsed.data.address.trim(),
        parsed.data.chainLabel.trim(),
        nowIso(),
        req.customerCaseId!,
      );
      const updated = getCase(req.customerCaseId!);
      writeAudit({
        type: "PUBLIC_ADDRESS_UPDATED",
        summary: "Customer shared a voluntary public wallet address",
        caseId: updated.id,
        meta: { chain: parsed.data.chainLabel.trim() },
      });
      const dto = serializeCase(updated);
      io.to(`case:${updated.id}`).emit("case:updated", dto);
      io.to("agents").emit("case:updated", dto);
      res.json({ case: dto });
    } catch (err) {
      next(err);
    }
  });

  router.post("/cases/me/messages", requireCustomerCase, async (req, res, next) => {
    try {
      const parsed = z.object({ body: z.string().trim().min(1).max(2000) }).safeParse(req.body);
      if (!parsed.success) {
        res.status(400).json({ error: "Invalid message" });
        return;
      }
      if (looksLikeSecretMaterial(parsed.data.body)) {
        res.status(400).json({
          error:
            "Message rejected: do not send passwords, one-time codes, seed phrases, or private keys.",
        });
        return;
      }
      const id = cuidLike();
      const createdAt = nowIso();
      db.prepare(
        `INSERT INTO messages (id, case_id, author_role, body, created_at)
         VALUES (?, ?, 'CUSTOMER', ?, ?)`,
      ).run(id, req.customerCaseId!, parsed.data.body, createdAt);
      db.prepare("UPDATE support_cases SET updated_at = ? WHERE id = ?").run(
        createdAt,
        req.customerCaseId!,
      );
      writeAudit({
        type: "MESSAGE_SENT",
        summary: "Customer sent a support message",
        caseId: req.customerCaseId!,
      });
      const payload = {
        id,
        caseId: req.customerCaseId!,
        authorRole: "CUSTOMER" as const,
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

  router.get("/cases/me/messages", requireCustomerCase, async (req, res, next) => {
    try {
      const rows = db
        .prepare(
          "SELECT id, case_id, author_role, body, created_at FROM messages WHERE case_id = ? ORDER BY created_at ASC",
        )
        .all(req.customerCaseId!) as Array<{
        id: string;
        case_id: string;
        author_role: string;
        body: string;
        created_at: string;
      }>;
      res.json({
        messages: rows.map((m) => ({
          id: m.id,
          caseId: m.case_id,
          authorRole: m.author_role,
          body: m.body,
          createdAt: m.created_at,
        })),
      });
    } catch (err) {
      next(err);
    }
  });

  return router;
}
