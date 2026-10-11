# Screenshot analysis — `screenshots\`1`

Inspected: 2026-10-06. Folder on disk is `screenshots\`1` (backtick in the name).

**Counts:** 241 files. PNG 56. SVG 5. HTML 88. CSS 73. WOFF2 12. TXT 4. ZIP 2. MP4 1. No JPG/JPEG/WEBP/PDF.

Secret values (recovery words, generated addresses, passwords, OTP digits) are **not copied** into this file.

---

## Implementation gate (read before Phase 2)

The pack is two products mixed together:

| Layer | What the files show | Harbor implementation |
|---|---|---|
| A. Admin chrome | Sign-in, empty Panel, Settings, session workspace, family chips, accordion cards, Send/Preview, live row fields | **Implement** (already largely present) |
| B. Visitor safe flow | Support landing, loading, waiting, case review, done, Harbor mark top-left | **Implement** on `/welcome` `/loading` `/waiting` `/contact` `/success` `/offline` |
| C. Brand-clone visitor dumps | Coinbase / Ledger / Trezor / Crypto.com / MetaMask / Exodus / Phantom / Trust / Ellipal login, seed, OTP, PIN, ID upload, vault drain | **Do not implement** |
| D. Harvest Admin cards | Seed entry, Fake Seed, Import Seed, Whitelist Seed, Vault clone, Upload ID/Selfie, AnyDesk, Captcha remote commands | **Do not implement** |

## Phase 2b UI polish (Harbor-safe, 2026-10-06)

Matched Admin chrome from login / empty Panel / Settings / within-session PNGs:
monospace titles, red login key border, rail `.active` pill, denser family chips,
centered Connected status, meta rows + session cards, Select blue / Preview purple.
Visitor continue cards: soft entrance + training banner monospace.
**Not** wired: brand HTML dumps, recovery-phrase preview modal, seed fields from cards.

## Phase 2 applied (Harbor-safe, 2026-10-06)

Implemented operational chrome from the inventory with non-sensitive labels:

- Accordion cards: Send/Preview; Default/Invalid; Hold/Resume
- Case Access Code suffix (not a secret PIN)
- Case Review + Last Activity Location/Time/Name
- Select Channel Chat/Phone/Desk
- Transfer Case Hold/Resume
- Live left-column Case / Visitor note / Display name
- Visitor Continue waits on Loading until Admin Send
- Draft parameters apply on Send only; Preview still does not join

Not implemented (out of scope): brand clones, recovery grids, OTP/PIN harvest, vault drain, ID upload, AnyDesk, Fake/Import/Whitelist Seed.


---

## Workflow (from screenshots + CONTEXT.txt)

Admin: URL key → strip key → Settings or Panel → session row → View → two-column workspace → pick family chip → expand accordion → optional draft fields → Preview (Admin-only iframe, no session) or Send (Connected only). Hidden tab = Waiting. X ends list row.

Visitor: starting page creates session (tab-isolated). Continue on a page goes to that family’s loading and waits. Admin Send moves the tab. Deep URL without a valid session = offline.

---

## Shared Admin chrome (all Admin screenshots)

- Role: Admin after login.
- Nav: left rail — Panel (sliders), Settings (gear). Logo ring-C at top of rail in Harbor (not in original empty-panel shot).
- Theme: black, grey borders, blue Send/Select, purple Preview.
- Desktop only (CONTEXT). No mobile Admin.
- Live updates: per-row, not full redraw. API: login, settings, session-list, presence, command, session:patch, demo-fields.
- Empty Panel: two chips “N sessions” + Enabled/Disabled. No cards.
- Filters/search: none.

---

## A. Unique Admin / overlay screenshots (every PNG)

For each: path, screen, Harbor route, purpose, role, nav, UI, data, API, prev/next, implement?

### Login

**admin login screen from my friends/Screenshot 2026-05-29 072912.png**  
Duplicate: `HTML/.../photos/example of admin login.png`  
Screen: Admin Sign in. Route: `/` unauthenticated (`?key=` optional). Purpose: one Admin key. Role: unauthenticated Admin. Nav: none. Sections: title Sign in; padlock password field (red border); Continue; Connected. Forms: Admin key. Buttons: Continue. Empty: empty field. Error: wrong key fails (not shown). Success: Settings/Panel. Responsive: centered desktop. Data: key. API: POST `/api/admin/login`. Next: Settings. **Implement.**

### Panel empty

**home screen of sessions/Screenshot 2026-05-29 073136.png**  
Screen: Panel 0 sessions. Route: Panel view. Purpose: empty live list. Role: Admin. Nav: Panel active. Sections: chips “0 sessions”, “Disabled”. Cards: none. Empty: yes. Data: session count, panel enabled. API: settings + session-list. Prev: login. Next: visitor join adds a card. **Implement.**

### Settings

**settings/Screenshot 2026-05-29 073229.png**  
Screen: Settings, family chips + page accordion (Ledger-style list in original). Route: Settings. Purpose: enable panel, sounds, starting page. Role: Admin. Nav: Settings. Toggles: Enable Panel, Notification Sounds. Chips: original brand names (Harbor substitutes Harbor / Account Desk / Product Desk / Case Desk / Help Desk). Accordion: Support Landing, Migrate, Select Device, Connect Device, Seed, Done. Actions: expand. **Brand page names: do not clone. Harbor accordion: implement.**

**settings/Screenshot 2026-05-31 073327.png**  
Screen: Settings expanded Security Actions. Extra: Location / Time / Name; Select + Preview; High Security header. Demo fields: implement on Case Review only. **Do not implement High Security harvest page.**

### Session workspace

**within a session/Screenshot 2026-05-29 073959.png**  
**HTML/.../photos/device + Connected.png** (Ledger family selected)  
Screen: selected session. Route: Panel + focused token. Left: Connected, Page, IP+geo, Device, User Agent. Right: family chips + accordion. Role: Admin. API: session-list, presence, command. Prev: View on card. Next: Send/Preview. **Implement chrome. Family chips Harbor-safe.**

**within a session/Screenshot 2026-06-16 111932.png**  
Same workspace; Crypto.COM selected; Login accordion Send/Preview; more OTP-named items collapsed. **Chrome implement. OTP/login harvest items: do not implement.**

**within a session/Screenshot 2026-05-29 074343.png**  
Left extra field “Ledger Seed” empty. Live secret box. **Do not implement secret live boxes.** Keep Page/IP/Device/UA/Case.

**within a session/Screenshot 2026-05-29 080315.png**  
Left: many seed boxes (Ledger/Trezor/MetaMask/Exodus/Phantom/Trust/Ellipal) + Captcha page. **Do not implement.**

### Admin accordion cards (right column)

Shared: Admin, from session workspace, Send (blue) / Preview (purple), optional Default/Invalid, draft dies on session switch.

| File | Card | Fields / extra | Implement? |
|---|---|---|---|
| admin cards/073254.png | Seed + device models | Nano S/X/Stax; Disconnect/Recover; Default/Invalid; Select/Preview | No (seed harvest) |
| admin cards/075003.png | Wallets family list | Seed/Loading rows per wallet brand | No |
| admin cards/075801.png | Google Case review | Location; Download transcript toggle; Login/Confirm/Captcha list | Demo Location only; no Google clone |
| admin cards/075956.png | Confirm Code | Code field | No (auth code) |
| admin cards/080821.png | Case Access Code | Default / PIN | Harbor Case Code page OK without PIN harvest |
| admin cards/080920.png | Case Access Code PIN | Case ID, Agent name | Non-secret case fields OK |
| admin cards/081331.png | Device Confirmation URL | Email; Default/Invalid | No (device-auth clone) |
| admin cards/081445.png | Reset Password | Default/Invalid | No |
| admin cards/081605.png | Security Actions | Location, Time, Name; Send/Preview | **Yes** (already wired) |
| admin cards/081754.png | High Security + list | AnyDesk, Seed Selector, Seed, Vault… | No |
| admin cards/081927.png | Seed Default/Invalid | then Disconnected/Vault list | No |
| admin cards/082104.png | Disconnected + Vault/Captcha/Upload ID | | No |
| admin cards/083111.png | Upload ID + Fake/Import/Whitelist Seed | | No |
| admin cards/083157.png | Link Wallet | Coinbase/Trust/Safepal/Other | No |
| admin cards/084204.png | Security Actions + Done/Loading | same demo fields | Security Actions yes; Done/Loading as Harbor pages yes |
| admin cards/115716.png | Fake Seed | Seed + regenerate | No |
| photos/admin cards.png | Coinbase card list | Support Landing through Last Activity | Harbor Support Home / Case Code / Loading only |
| photos/coinbase download wallet.png | Download App | brand picker | No |
| photos/coinbase import.png | Import Seed | brand picker + abusive toggle | No |
| photos/coinbase whitelist.png | Whitelist Seed | brand + Default/Invalid | No |

### Vault Admin + visitor (all harvest)

Do not copy recovery text or addresses from these files.

| File | Screen | Implement? |
|---|---|---|
| vault/082344.png | Vault empty + generated secret dropdown | No |
| vault/082405.png | Vault assets rows + secret | No |
| vault/082430.png | Admin left live seed boxes + asset table | No |
| vault/113312.png | Fake Seed + Vault empty | No |
| vault/120813.png | Visitor Select an asset | No |
| vault/120828.png | Visitor Deposit + QR | No |
| photos/vault asset selection.png | Admin empty vault vs left seed boxes | No |
| photos/vault + boxes in the admin ui once sent.png | Duplicate of 082430 | No |
| coinbase/vault/before generatating the bip39 seed.png | Vault empty generate | No |
| coinbase/vault/vault select asset before deposit.png | Duplicate 120813 | No |
| coinbase/vault/what it looks like from the admin card.png | Duplicate 082405 | No |
| coinbase/vault/deposit of vault example qr code.png | QR deposit (same class as 120828) | No |

### Preview overlays (Admin-only; visitor not joined)

Route conceptually: Preview iframe `?preview=1`. API: none for join. **Brand login / seed grids: do not ship. Harbor preview of `/welcome` etc.: yes.**

| File | Visitor content shown | Implement Harbor equivalent? |
|---|---|---|
| preview/081049.png | Coinbase Sign in (email, Google/Apple/Wallet) | No. Harbor welcome/contact preview |
| coinbase/preview/081049.png | Duplicate | No |
| preview/112036.png | Disconnect wallet + provider dropdown + Continue | No |
| preview/115446.png | Robinhood-style login split | No |
| preview/115752.png | Trezor disconnect split | No |
| preview/115827.png | Trezor 24-word recover grid | No |
| preview/preview.png | Ledger recover 24/18/12 + passphrase | No |

---

## B. MP4

**A VIDEO of what it should sort of look like/IMG_3184.MP4** (15.8 MB)  
Phone recording of Admin/visitor flow matching the screenshots (family chips, Send/Preview, branded visitor overlays). Used as motion reference for chrome, not as license to clone brands. ffmpeg was unavailable in this environment; stills in `preview/` and `within a session/` cover the frames.

---

## C. HTML visitor dumps (88 files) — inventory only

These are cleaned saved-page clones. Titles confirmed. **Not mapped to Harbor routes.**

### Coinbase (35 HTML)

AccessCode, AccessPin, AnyDesk, AuthenticatorCode, BackupWallet, ConnectVault, CreateVault, DeviceConfirmationPage, DisconnectWallet, DisconnectedDone, DownloadCoinbaseWallet, EmailCodePage, EstimatedBalance, HighSecurity, ImportRecoveryPhrase, LastActivity, LinkCoinbase, PasswordPage, RepresentativeContactPage, ResetPasswordPage, ReviewAccount, SMSCodePage, SecureTransfer, Seed, SelectIDtype, SelectVaultAssets, Sign-In, Support-Landing, VerifyIdentity, WhitelistCoinbaseWallet, WithdrawAssets, done, loading, vault-balance, what the vault looks like with assets.

CSS 2.css–33.css plus named CSS; fonts Coinbase*.woff2; Coinbaselogo.png; vault.zip; vault.txt (not pasted).

### Crypto.com (24 HTML)

HelpCenter, access-code, connect-vault, create-vault, device-management, disconnect-your-wallet, download-crypto-mobile, enable-high-security, last-account-activity, loading, login_new, mobile-number-verification, review-your-account (before/next), securely-transfer-assets, select-vault-assets, vault-deposit-select, vault-withdraw, verify-authenticator-code, verify-email, wallet-disconnected, whitelist-success, whitelist-wallet.

### Ledger (8 HTML)

loading, page-1, page-1-ty8, page-2, page-2-ty8, page-3, page-3-ty8, signin + logos/CSS.

### Trezor (7 HTML)

connect, disconnect (main page), done, loading, recover, Recover passphrase wallet, recover standard wallet.

### MetaMask / Exodus / Phantom / Trust / Ellipal

3 HTML each (landing/disconnect, loading, recover) except Ellipal `1.html` + loading + recover.

### Supporting

photos.zip (391 KB) duplicates photos/. Brand SVG/PNG logos: Coinbase, Crypto.com, Ellipal, Exodus, Ledger, MetaMask fox, Phantom, Trezor.

---

## D. Spec text files (not screenshots; inspected)

- `HTML/.../CONTEXT.txt` — product rules: tab sessions, Connected/Idle, no Send queue, Preview isolated, settings, exit URL, live row updates, vault/seed keystroke store. Harbor keeps session/presence/Send/Preview/settings. Drops seed-keystroke and vault clone.
- `HTML/.../DEVELOPER-PROCESS.txt` — TS/React Admin, Node, Postgres, WS; visitor = supplied HTML. Conflicts with harvest ban: use Harbor HTML, not dumps.
- `coinbase/vault/vault.txt` — vault generation rules. Not implemented.
- `coinbase/vault/vault explained.txt` — empty.

---

## Harbor route map (allowed)

| Harbor route | Role | Screenshot analog | Status |
|---|---|---|---|
| Admin `/` login | Admin | 072912 | Implemented |
| Admin Settings | Admin | 073229 / 073327 | Implemented (Harbor chips) |
| Admin Panel empty | Admin | 073136 | Implemented |
| Admin session cards | Admin | session list (implied) | Implemented |
| Admin workspace | Admin | 073959 / device+Connected | Implemented |
| `/welcome` | Visitor | Support landing analog | Implemented + Harbor logo |
| `/loading` | Visitor | family loading | Implemented |
| `/waiting` | Visitor | waiting | Implemented |
| `/contact` | Visitor | Case Review analog | Implemented |
| `/success` | Visitor | Done analog | Implemented |
| `/offline` | Visitor | panel disabled | Implemented |
| `/demo/family-a/*` `/demo/family-b/*` | Visitor | generic desks | Implemented, not brand clones |
| Preview `?preview=1` | Admin only | overlay pattern | Implemented for Harbor pages |

---

## Gaps vs original screenshots (allowed work only)

1. Settings accordion still lists Harbor pages; original had ~20 brand chips — **will not add brand chips**.
2. Session left column will not grow secret boxes.
3. Send still 409 when Waiting; no queue.
4. Exit URL / offline URL split from CONTEXT is product settings, not harvest.
5. Logout control (CONTEXT: bottom left) is not in screenshots; optional later.
6. Notification sound on join/loading — settings toggle exists; sound file optional.
7. Phase 2 prompt was cut off; no harvest reconstruction.

---

## Files not image-screenshots but listed for completeness

CSS/fonts/zips/SVG logos: styling assets for refused dumps. Not Admin routes.
