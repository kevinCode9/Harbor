import type { AuditType } from "@harbor/shared";
import { db } from "../db/client.js";
import { cuidLike, nowIso } from "./ids.js";

export function writeAudit(input: {
  type: AuditType;
  summary: string;
  caseId?: string | null;
  agentId?: string | null;
  meta?: Record<string, unknown> | null;
}): void {
  db.prepare(
    `INSERT INTO audit_events (id, case_id, agent_id, type, summary, meta_json, created_at)
     VALUES (?, ?, ?, ?, ?, ?, ?)`,
  ).run(
    cuidLike(),
    input.caseId ?? null,
    input.agentId ?? null,
    input.type,
    input.summary,
    input.meta ? JSON.stringify(input.meta) : null,
    nowIso(),
  );
}
