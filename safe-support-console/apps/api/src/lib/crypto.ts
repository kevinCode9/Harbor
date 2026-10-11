import { createHash, randomBytes, timingSafeEqual } from "node:crypto";

export function generateToken(bytes = 32): string {
  return randomBytes(bytes).toString("base64url");
}

export function hashToken(token: string): string {
  return createHash("sha256").update(token).digest("hex");
}

export function safeEqualHex(a: string, b: string): boolean {
  const ba = Buffer.from(a, "hex");
  const bb = Buffer.from(b, "hex");
  if (ba.length !== bb.length) return false;
  return timingSafeEqual(ba, bb);
}

/** Human-friendly case number: HBR-XXXXXX */
export function generateCaseNumber(): string {
  const n = randomBytes(3).readUIntBE(0, 3) % 1_000_000;
  return `HBR-${String(n).padStart(6, "0")}`;
}

/**
 * Reject strings that look like recovery phrases / hex keys / OTP blobs
 * so they are never accepted into contact or message fields.
 */
export function looksLikeSecretMaterial(value: string): boolean {
  const v = value.trim();
  if (!v) return false;
  const words = v.toLowerCase().split(/\s+/).filter(Boolean);
  if (words.length >= 12 && words.every((w) => /^[a-z]+$/.test(w))) return true;
  if (/^(0x)?[a-fA-F0-9]{64}$/.test(v)) return true;
  if (/^\d{6}$/.test(v)) return true;
  return false;
}

/** Very light public-address shape check (BTC / ETH / Sol-ish). */
export function isPlausiblePublicAddress(address: string): boolean {
  const a = address.trim();
  if (a.length < 26 || a.length > 90) return false;
  if (looksLikeSecretMaterial(a)) return false;
  if (/^0x[a-fA-F0-9]{40}$/.test(a)) return true;
  if (/^[13][a-km-zA-HJ-NP-Z1-9]{25,34}$/.test(a)) return true;
  if (/^bc1[a-z0-9]{25,62}$/i.test(a)) return true;
  if (/^[1-9A-HJ-NP-Za-km-z]{32,44}$/.test(a)) return true;
  return false;
}
