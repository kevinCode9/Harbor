/**
 * Editable UI / Settings labels. Change strings here (and the visitor twin
 * apps/visitor-web/js/ui-copy.js) — do not hardcode brand login or secret capture copy.
 */
export const UI_COPY = {
  trainingBanner:
    'SECURITY TRAINING SIMULATION — Educational demo only. Do not enter real passwords, recovery phrases, private keys, OTPs, SMS/TOTP codes, tokens, or wallet credentials. Uses fictitious Harbor desks only — never real company branding.',

  families: {
    harbor: 'Coinbase',
    mailDesk: 'Gmail',
    accountDesk: 'Crypto.com',
    productDesk: 'Product Desk',
    caseDesk: 'Case Desk',
    helpDesk: 'Help Desk',
  },

  harbor: {
    welcome: {
      title: 'Continue your case',
      invalidHeading: 'That name was not accepted',
      label: 'Display name',
      placeholder: 'Your display name',
      submit: 'Continue',
      or: 'OR',
      altChat: 'Continue with Chat',
      altPhone: 'Continue with Phone',
      altDesk: 'Continue with Desk',
      documentTitle: 'Harbor Support — Continue',
    },
  },

  /** Settings / Support Console chip labels for Mail Desk (flow shape only). */
  mailDeskMenu: {
    signin: 'Account Sign-in',
    invalid: 'Incorrect Entry',
    verify: '2-Step Verification',
    code: 'App Code Step',
    waiting: 'Processing / Waiting',
    alert: 'Security Notification',
    device: 'Device Verification',
  },

  mailDesk: {
    signin: {
      title: 'Account sign-in',
      lead: 'Enter a display name for this training case. Not a brand login.',
      label: 'Display name',
      placeholder: 'Training display name',
      submit: 'Continue',
      invalidHeading: 'That name was not accepted',
      documentTitle: 'Harbor Mail Desk — Account Sign-in',
    },
    invalid: {
      title: 'Incorrect entry',
      lead: 'The previous display name was not accepted for this training case. Try again with a non-secret label.',
      label: 'Display name',
      placeholder: 'Training display name',
      submit: 'Try again',
      invalidHeading: 'That entry was not accepted',
      documentTitle: 'Harbor Mail Desk — Incorrect Entry',
    },
    verify: {
      title: '2-step verification',
      lead: 'An agent asked you to confirm you are still on this Mail Desk tab. No codes or passwords are collected here.',
      submit: 'Confirm step',
      documentTitle: 'Harbor Mail Desk — 2-Step Verification',
    },
    code: {
      title: 'App code step',
      lead: 'Enter the public 6-character case suffix from your ticket. Do not enter authenticator or SMS codes.',
      submit: 'Continue',
      invalidHeading: 'Case suffix not accepted',
      shortError: 'Enter all six characters of the public case suffix.',
      otpReject: 'Use the public case suffix from the ticket, not a 6-digit authenticator code.',
      documentTitle: 'Harbor Mail Desk — App Code Step',
    },
    waiting: {
      title: 'Processing / waiting',
      lead: 'Please keep this tab open. An agent is reviewing the Mail Desk step for your training case.',
      documentTitle: 'Harbor Mail Desk — Processing / Waiting',
    },
    alert: {
      title: 'Security notification',
      lead: 'This training notice reminds you never to enter real passwords, recovery material, or authenticator codes on support pages. Acknowledge to continue the drill.',
      submit: 'I understand',
      documentTitle: 'Harbor Mail Desk — Security Notification',
    },
    device: {
      title: 'Device verification',
      lead: 'Confirm that this is the browser tab an agent should treat as the active Mail Desk session for your training case.',
      submit: 'Confirm this tab',
      reject: 'Not this tab',
      documentTitle: 'Harbor Mail Desk — Device Verification',
    },
  },
} as const;
