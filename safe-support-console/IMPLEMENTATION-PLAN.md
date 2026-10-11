# Harbor Support — Implementation Plan

## Relation to milady

This folder is a **new** legitimate support application.
It does **not** reuse milady visitor pages, capture APIs, or vault code.
milady PHP/HTML above this folder is left untouched.

### Safe to reuse (concepts only)

| Concept | How used here |
|---|---|
| Operator case/session list | Support cases (non-secret) |
| Realtime client updates | Socket.IO for status + messages only |
| Hashed operator login | bcrypt agent passwords |

### Must not reuse

| milady component | Reason |
|---|---|
| Brand HTML under `resources/views/user/**` | Impersonation / harvesting UI |
| `CapturedPhrase`, `/api/submit`, `/save-phrase` | Secret collection |
| `VaultWalletService`, BIP39 deps | Wallet secret generation |
| `/api/log` form/keystroke harvest | Sensitive exfiltration |
| Admin send-to-login/SMS/seed pages | Social-engineering navigation |

## Product

Customer: Welcome → Case ID → optional Contact → Status → Waiting → Success  
Optional: voluntary public wallet address only (no seed/key/OTP/password).  
Agent: login, cases, messages, status, internal notes, audit trail.  
Branding: original “Harbor Support”.

## Stack

npm workspaces · Express + Node `sqlite` + Socket.IO · Vite React TS

No deployment in this task.

## Status

Implemented under `safe-support-console/`. milady PHP/HTML left untouched.
Local smoke: health, case create, seed-like message/address rejected, agent login OK.
