/** Safe customer portal screens — no secret-collection screens exist. */
export const CLIENT_SCREENS = [
  "WELCOME",
  "CASE_ID",
  "CONTACT",
  "STATUS",
  "WAITING",
  "SUCCESS",
] as const;

export type ClientScreen = (typeof CLIENT_SCREENS)[number];

export const CASE_STATUSES = [
  "OPEN",
  "WAITING_ON_CUSTOMER",
  "WAITING_ON_AGENT",
  "RESOLVED",
  "CLOSED",
] as const;

export type CaseStatus = (typeof CASE_STATUSES)[number];

export const AUDIT_TYPES = [
  "CASE_CREATED",
  "SCREEN_CHANGED",
  "STATUS_CHANGED",
  "MESSAGE_SENT",
  "NOTE_ADDED",
  "CONTACT_UPDATED",
  "PUBLIC_ADDRESS_UPDATED",
  "AGENT_LOGIN",
  "CASE_CLOSED",
] as const;

export type AuditType = (typeof AUDIT_TYPES)[number];

export type SafeContact = {
  name?: string;
  email?: string;
};

/** Voluntary public address only — never a seed or private key. */
export type PublicWalletRef = {
  chainLabel: string;
  address: string;
};

export type SupportMessageDto = {
  id: string;
  caseId: string;
  authorRole: "CUSTOMER" | "AGENT";
  body: string;
  createdAt: string;
};

export type SupportCaseDto = {
  id: string;
  caseNumber: string;
  status: CaseStatus;
  currentScreen: ClientScreen;
  contactName: string | null;
  contactEmail: string | null;
  publicAddress: string | null;
  publicAddressChain: string | null;
  createdAt: string;
  updatedAt: string;
};

export const SAFE_SCREEN_LABELS: Record<ClientScreen, string> = {
  WELCOME: "Welcome",
  CASE_ID: "Case ID",
  CONTACT: "Contact (optional)",
  STATUS: "Support status",
  WAITING: "Waiting",
  SUCCESS: "Completed",
};
