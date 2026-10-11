# Harbor Support

Legitimate customer-support console (customer portal + agent console).

**Not milady.** No brand-clone login/wallet pages. Never collects seed phrases, private keys, customer passwords, OTPs, authenticator codes, or recovery phrases.

Optional wallet data is a **voluntary public address** only (read-only). No transfers.

## Layout

| Path | Role |
|---|---|
| `apps/web` | Portal `/` and agent `/agent` |
| `apps/api` | Express + Socket.IO + Node SQLite |
| `packages/shared` | Safe screen/status types |

## Local run (no deploy)

```bash
cd safe-support-console
npm install
npm run build -w @harbor/shared
npm run db:push -w @harbor/api
npm run db:seed -w @harbor/api
npm run dev:api   # http://127.0.0.1:4000
npm run dev:web   # http://127.0.0.1:5173
```

Default agent (change after first login):

- Email: `agent@harbor.local`
- Password: `ChangeMeOnFirstLogin!`

## Safe screens

`WELCOME` → `CASE_ID` → `CONTACT` (optional) → `STATUS` / `WAITING` / `SUCCESS`
