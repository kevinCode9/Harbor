import { randomBytes } from "node:crypto";

export function cuidLike(): string {
  return randomBytes(16).toString("hex");
}

export function nowIso(): string {
  return new Date().toISOString();
}
