import type { AdminAction } from '../state/core-state.js';

export type SendAction = AdminAction;

/** Wire payload carrying AdminDraft fields into the Send pipeline. */
export type SendCommand = {
  sessionToken: string;
  action: SendAction;
  page?: string;
  selectedFamily?: string;
  temporaryParameters?: Record<string, unknown>;
};
