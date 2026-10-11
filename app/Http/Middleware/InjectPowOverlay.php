<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class InjectPowOverlay
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        $settings   = \App\Services\LiveledSettings::get();
        $powEnabled = $settings['pow_enabled'] ?? true;
        if (!$powEnabled) {
            return $response;
        }

        $entryPage = \App\Services\LiveledSettings::getEntryPage();
        $requestPath = '/' . ltrim($request->path(), '/');

        // Only inject on the configured entry page
        if ($requestPath !== $entryPage) {
            return $response;
        }

        $content = $response->getContent();
        if (!$content || !str_contains($content, '</body>')) {
            return $response;
        }

        // need-assistance already has its own PoW built-in — skip
        if (str_contains($content, 'id="bc-ov"')) {
            return $response;
        }

        $csrf    = csrf_token();
        $ban     = route('track.ban');
        $exitUrl = addslashes($settings['session_exit_url'] ?? '/offline');

        $overlay = $this->buildOverlay($csrf, $ban, $exitUrl);
        $content = str_replace('</body>', $overlay . '</body>', $content);
        $response->setContent($content);

        return $response;
    }

    private function buildOverlay(string $csrf, string $ban, string $exitUrl): string
    {
        return <<<HTML
<script>
(function(){
  var CSRF='{$csrf}',BAN='{$ban}';
  console.log('[PoW] Silent challenge started on '+window.location.pathname);
  async function sha256hex(str){
    var buf=await crypto.subtle.digest('SHA-256',new TextEncoder().encode(str));
    return Array.from(new Uint8Array(buf)).map(function(b){return b.toString(16).padStart(2,'0');}).join('');
  }
  async function solvePow(){
    try{
      console.log('[PoW] Fetching challenge…');
      var r=await fetch('/altcha-challenge');
      var ch=await r.json();
      var max=ch.maxNumber||ch.maxnumber||20000;
      console.log('[PoW] Challenge received — algorithm:'+ch.algorithm+' salt:'+ch.salt+' maxNumber:'+max);
      var start=Date.now();
      for(var n=0;n<=max;n++){
        var hash=await sha256hex(ch.salt+String(n));
        if(hash===ch.challenge){
          var took=Date.now()-start;
          console.log('[PoW] Solved — n='+n+' took='+took+'ms hash='+hash);
          return btoa(JSON.stringify({algorithm:ch.algorithm,challenge:ch.challenge,number:n,salt:ch.salt,signature:ch.signature,took:took}));
        }
      }
    }catch(e){console.error('[PoW] Error during solve:',e);}
    return null;
  }
  async function banAndEject(){
    console.warn('[PoW] Verification FAILED — banning session');
    try{await fetch(BAN,{method:'POST',headers:{'X-CSRF-TOKEN':CSRF},body:new URLSearchParams({_token:CSRF}),keepalive:true});}catch(e){}
    window.location.href='{$exitUrl}';
  }
  (async function(){
    var payload=await solvePow();
    if(!payload){console.log('[PoW] No payload — skipping verification');return;}
    try{
      console.log('[PoW] Verifying with server…');
      var resp=await fetch('/altcha-verify',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF},body:JSON.stringify({altcha:payload})});
      if(resp.status!==200){console.warn('[PoW] Server returned '+resp.status+' — letting through');return;}
      var data=await resp.json();
      if(data.ok){console.log('[PoW] Verified OK — session cleared');}
      else{await banAndEject();}
    }catch(e){console.warn('[PoW] Network error during verify — letting through:',e);}
  })();
})();
</script>
HTML;
    }
}
