import type { CaseStatus, ClientScreen, SupportCaseDto } from "@harbor/shared";
import type { CaseRow } from "../db/client.js";

export function serializeCase(row: CaseRow): SupportCaseDto {
  return {
    id: row.id,
    caseNumber: row.case_number,
    status: row.status as CaseStatus,
    currentScreen: row.current_screen as ClientScreen,
    contactName: row.contact_name,
    contactEmail: row.contact_email,
    publicAddress: row.public_address,
    publicAddressChain: row.public_address_chain,
    createdAt: row.created_at,
    updatedAt: row.updated_at,
  };
}
