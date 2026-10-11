<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"/>
<link rel="icon" href="/Coinbaselogo.png"/>
<title>Coinbase Help</title>
<meta content="initial-scale=1.0, width=device-width" name="viewport"/>
<link href="css/zf2.css" rel="stylesheet"/>
<style>
html, body { background: #ffffff; background-color: #ffffff; margin: 0; font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif; }
.zf2-chrome { position: absolute; top: 0; width: 100%; z-index: 2; }
.zf2-header { display: flex; height: 60px; width: 100%; justify-content: center; background: #ffffff; border-bottom: 1px solid #e5e7eb; box-sizing: border-box; }
.zf2-header-inner { display: flex; height: 100%; width: 100%; max-width: 1228px; align-items: center; justify-content: space-between; padding: 0 24px; box-sizing: border-box; }
.zf2-nav { display: none; height: 100%; align-items: center; gap: 32px; font-size: 14px; font-weight: 600; }
.zf2-wordmark { height: 20px; width: 150px; }
.zf2-main { display: flex; flex-direction: column; align-items: center; background: #ffffff; padding-top: 166px; }
.zf2-hero { margin-top: 48px; display: flex; flex-direction: column; align-items: center; gap: 8px; text-align: center; font-size: 48px; font-weight: 400; line-height: 1.15; }
.zf2-hero h1 { margin: 0; font-size: inherit; font-weight: inherit; line-height: inherit; }
.zf2-search-wrap { margin-top: 24px; width: 100%; max-width: 625px; padding: 0 16px; box-sizing: border-box; }
.zf2-case-wrap { margin-top: 48px; width: 100%; max-width: 760px; padding: 0 16px; box-sizing: border-box; }
.zf2-section { margin-top: 64px; width: 100%; max-width: 1228px; padding: 0 24px; box-sizing: border-box; }
.zf2-section-title { font-size: 40px; line-height: 1.2; }
.zf2-top-grid { margin-top: 48px; display: grid; grid-template-columns: 1fr; gap: 32px; }
.zf2-topic-grid { margin-top: 32px; display: grid; width: 100%; grid-template-columns: 1fr 1fr; gap: 48px; }
.zf2-signin { height: 40px; border: none; border-radius: 9999px; background: #1652f0; color: #ffffff; padding: 0 32px; font-weight: 600; }
.zf2-banner { display: flex; width: 100%; justify-content: center; background: #2151f6; color: #ffffff; padding: 32px 0; box-sizing: border-box; }
.zf2-banner-inner { display: flex; width: 100%; max-width: 1228px; align-items: center; justify-content: space-between; padding: 0 24px; gap: 24px; box-sizing: border-box; }
.zf2-banner-copy { display: flex; align-items: center; gap: 24px; flex-grow: 1; }
.zf2-banner-text { font-size: 14px; font-weight: 700; }
.zf2-banner-text a { color: inherit; text-decoration: underline; }
.zf2-dismiss { height: 42px; border: none; border-radius: 4px; background: #ffffff; color: #000000; padding: 0 20px; font-size: 14px; font-weight: 600; display: none; }
.zf2-dismiss-x { display: block; background: transparent; border: none; color: #ffffff; padding: 0; }
.zf2-dismiss-x svg { height: 16px; width: 16px; }
.zf2-search { display: flex; align-items: center; overflow: hidden; border-radius: 9999px; box-shadow: 0 0 0 1px #9ca3af; background: #ffffff; }
.zf2-search-icon { height: 100%; padding: 0 8px 0 32px; border: none; background: transparent; }
.zf2-search input { flex-grow: 1; height: 100%; border: none; background: transparent; padding: 15px; font-size: 20px; outline: none; font-family: inherit; }
.zf2-case { display: flex; gap: 16px; border-radius: 8px; background: #f5f8fe; padding: 24px 16px; box-shadow: 0 0 0 1px #d3e1ff; }
.zf2-case-copy { display: flex; flex-direction: column; gap: 4px; }
.zf2-case-title { font-weight: 600; }
.zf2-case-note { font-size: 14px; color: #5b616e; }
.zf2-case-btn { margin-top: 16px; display: flex; width: 13rem; height: 40px; align-items: center; justify-content: center; border: none; border-radius: 9999px; background: #1652f0; color: #ffffff; font-weight: 600; padding: 0; }
.zf2-top-head { font-weight: 600; }
.zf2-top-list { margin-top: 16px; display: flex; flex-direction: column; }
.zf2-top-row { display: flex; align-items: center; justify-content: space-between; }
.zf2-top-row + .zf2-top-row { border-top: 1px solid #e5e7eb; }
.zf2-top-row a { width: 100%; padding: 16px 0; }
.zf2-topic { display: flex; aspect-ratio: 1 / 1; width: 100%; flex-direction: column; align-items: center; justify-content: center; gap: 16px; padding: 32px; box-sizing: border-box; text-align: center; font-size: 20px; border-radius: 8px; box-shadow: 0 0 0 1px #ced2db; }
.zf2-topic svg { width: 64px; height: 64px; }
.zf2-footer { display: flex; width: 100%; flex-direction: column; justify-content: space-between; gap: 16px; margin: 96px 0 64px; padding: 0 48px; box-sizing: border-box; }
.zf2-foot-brand { display: flex; flex-direction: column; align-items: flex-start; gap: 8px; }
.zf2-foot-logo { height: 20px; width: auto; }
.zf2-foot-copy { margin-top: 16px; }
.zf2-foot-cols { display: grid; grid-template-columns: 1fr; gap: 16px; }
.zf2-foot-col { display: flex; flex-direction: column; gap: 4px; }
.zf2-foot-col-title { margin-bottom: 8px; font-weight: 600; }
.zf2-foot-col-title + .zf2-foot-col-title, .zf2-foot-col a + .zf2-foot-col-title { margin-top: 16px; }
button { font: inherit; }
html { line-height: 1.15; }
@media (min-width: 768px) {
  .zf2-nav { display: flex; }
  .zf2-dismiss { display: block; }
  .zf2-dismiss-x { display: none; }
  .zf2-top-grid { grid-template-columns: 1fr 1fr; }
  .zf2-footer { flex-direction: row; padding: 0 88px; }
  .zf2-foot-brand { width: 33.333%; }
  .zf2-foot-cols { width: 66.666%; grid-template-columns: 1fr 1fr 1fr; gap: 0; }
}
@media (min-width: 1024px) {
  .zf2-topic-grid { grid-template-columns: 1fr 1fr 1fr; }
}
@media (min-width: 1280px) {
  .zf2-topic-grid { grid-template-columns: 1fr 1fr 1fr 1fr; }
}
</style>
</head>
<body>
<div id="root">
<div class="zf2-page">
<div class="zf2-chrome">
<header class="zf2-header">
<div class="zf2-header-inner">
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 698 85" class="zf2-wordmark"><path fill="#0052FF" d="M29.699 36.457c6.986 0 12.525 4.368 14.618 10.85h14.065c-2.555-13.881-13.85-23.286-28.591-23.286C13.08 24.037 0 36.908 0 54.132s12.741 30.002 29.806 30.002c14.403 0 25.929-9.404 28.468-23.395H44.317c-2 6.498-7.54 10.975-14.51 10.975-9.633 0-16.404-7.493-16.404-17.566 0-10.198 6.647-17.69 16.296-17.69Zm223.355-12.42c-7.417 0-13.634 3.14-18.066 8.394V.75h-13.295v82.265h13.08v-7.617c4.432 5.487 10.741 8.736 18.281 8.736 15.957 0 28.036-12.762 28.036-30.002s-12.31-30.095-28.036-30.095Zm-2.001 47.677c-9.525 0-16.511-7.493-16.511-17.566 0-10.074 7.094-17.69 16.619-17.69 9.633 0 16.403 7.492 16.403 17.69 0 10.057-6.986 17.566-16.511 17.566ZM141.599 1.31c-4.878 0-8.525 3.575-8.525 8.503s3.663 8.503 8.525 8.503c4.863 0 8.525-3.575 8.525-8.503s-3.662-8.503-8.525-8.503Zm48.303 22.727c-8.648 0-14.296 3.575-17.619 8.612v-7.493h-13.188v57.86h13.295V51.566c0-8.845 5.54-15.11 13.742-15.11 7.648 0 12.402 5.488 12.402 13.431v33.127h13.296V48.878c0-14.55-7.417-24.841-21.928-24.841Zm-63.26 13.089h8.31v45.89h13.295v-57.86h-21.605v11.97Zm252.608 11.41-9.756-1.461c-4.647-.669-7.971-2.239-7.971-5.939 0-4.026 4.324-6.047 10.187-6.047 6.432 0 10.525 2.798 11.417 7.384h12.849c-1.446-11.643-10.31-18.467-23.928-18.467-14.064 0-23.374 7.275-23.374 17.566 0 9.855 6.094 15.56 18.388 17.348l9.756 1.461c4.771.669 7.417 2.58 7.417 6.156 0 4.586-4.647 6.498-11.079 6.498-7.863 0-12.295-3.249-12.957-8.177H347.12c1.215 11.302 9.971 19.245 25.928 19.245 14.511 0 24.159-6.715 24.159-18.25-.015-10.26-7.001-15.638-17.957-17.317Zm-286.292-24.5c-16.726 0-29.806 12.872-29.806 30.112S75.893 84.15 92.958 84.15s30.022-12.98 30.022-30.111c0-17.022-12.741-30.002-30.022-30.002Zm.108 47.678c-9.525 0-16.511-7.493-16.511-17.566 0-10.182 6.863-17.69 16.403-17.69 9.633 0 16.619 7.616 16.619 17.69 0 10.073-6.986 17.566-16.511 17.566ZM336.702 44.96c0-12.53-7.54-20.924-23.482-20.924-15.064 0-23.482 7.726-25.144 19.587h13.188c.662-4.586 4.216-8.395 11.741-8.395 6.755 0 10.079 3.016 10.079 6.716 0 4.819-6.094 6.047-13.634 6.824-10.187 1.12-22.82 4.695-22.82 18.126 0 10.415 7.648 17.13 19.835 17.13 9.525 0 15.511-4.026 18.496-10.415.446 5.705 4.647 9.405 10.526 9.405h7.755v-11.97h-6.54V44.961Zm-13.08 14.55c0 7.726-6.647 13.43-14.741 13.43-4.986 0-9.202-2.129-9.202-6.606 0-5.705 6.755-7.275 12.957-7.943 5.985-.56 9.309-1.897 10.971-4.478v5.597h.015Zm108.916-35.474c-17.065 0-29.576 12.98-29.576 30.11 0 18.018 13.403 30.003 29.807 30.003 13.849 0 24.713-8.286 27.482-20.038h-13.849c-2 5.146-6.863 8.053-13.402 8.053-8.525 0-14.957-5.38-16.404-14.768h43.979V52.25c-.016-16.587-11.972-28.214-28.037-28.214ZM417.35 47.759c2.108-8.053 8.094-11.97 14.957-11.97 7.54 0 13.295 4.368 14.619 11.97H417.35Z"></path><path fill="#0A0B0D" fill-rule="evenodd" d="M499.824 48.54V24H491v58.675h8.824V56.188h29.739v26.487h8.824V24h-8.824v24.54h-29.739Zm93.802-16.893V24h-40.23v58.675h40.23v-7.647H562.22v-18.84h28.905V48.68H562.22V31.647h31.406ZM614.402 24v51.028h29.53v7.647h-38.355V24h8.825Zm63.715 0h-25.361v58.744h8.824V58.97h16.537c12.646 0 19.734-7.161 19.734-17.52 0-10.358-7.088-17.449-19.734-17.449Zm-.278 7.578c7.296 0 10.979 3.545 10.979 9.315v1.043c0 5.77-3.613 9.385-10.979 9.385H661.58V31.578h16.259Z" clip-rule="evenodd"></path></svg>
<nav class="zf2-nav">
<a href="javascript:void(0)">Individuals</a>
<a href="javascript:void(0)">Businesses</a>
<a href="javascript:void(0)">Developers</a>
</nav>
<button class="zf2-signin" type="button" onclick="return false;">Sign in</button>
</div>
</header>
<div class="zf2-banner">
<div class="zf2-banner-inner">
<div class="zf2-banner-copy">
<svg xmlns="http://www.w3.org/2000/svg" class="zf2-banner-info" width="24" height="24" fill="white"><path d="M12 0C5.37 0 0 5.37 0 12s5.37 12 12 12 12-5.37 12-12S18.63 0 12 0Zm1 19h-2v-2h2v2Zm0-4h-2V5h2v10Z"></path></svg>
<span class="zf2-banner-text">Exploring crypto again? Check out our <a href="javascript:void(0)">Returning Customer Resource Guide</a>.</span>
</div>
<div>
<button class="zf2-dismiss" type="button" data-zf2-dismiss="1">Dismiss</button>
<button class="zf2-dismiss-x" type="button" data-zf2-dismiss="1" aria-label="Dismiss"><svg xmlns="http://www.w3.org/2000/svg" fill="none" class="zf2-x" viewBox="0 0 16 16"><path fill="currentColor" d="m11.528 3.53.941.94-7.999 8-.94-.941z"></path><path fill="currentColor" d="m3.528 4.47.941-.94 8 7.998-.942.942z"></path></svg></button>
</div>
</div>
</div>
</div>
<main class="zf2-main">
<div class="zf2-hero">
<h1>Hi there,</h1>
<h1>How can we help you?</h1>
</div>
<div class="zf2-search-wrap">
<form class="zf2-search" action="#" method="get" onsubmit="return false;">
<button class="zf2-search-icon" type="button" tabindex="-1"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 16 16" width="16" height="16" aria-hidden="true"><circle cx="7" cy="7" r="5" stroke="currentColor" stroke-width="1.5"></circle><path d="M11 11l3.25 3.25" stroke="currentColor" stroke-linecap="round" stroke-width="1.5"></path></svg></button>
<input type="search" placeholder="Search for issues" value="" aria-label="Search for issues"/>
</form>
</div>
<div class="zf2-case-wrap">
<div class="zf2-case">
<span class="zf2-case-icon"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 22 22" width="22" height="22" aria-hidden="true"><circle cx="11" cy="11" r="9" fill="none" stroke="currentColor" stroke-width="2"></circle><circle cx="11" cy="7" r="1.25" fill="currentColor"></circle><path d="M11 10.25v5.5" stroke="currentColor" stroke-linecap="round" stroke-width="2"></path></svg></span>
<div class="zf2-case-copy">
<span class="zf2-case-title">Enter a case number for personalized support</span>
<span class="zf2-case-note">We will be able to identify and resolve issues more easily.</span>
<button class="zf2-case-btn" type="button" onclick="window.location.href='/coinbase/loading'">Enter case number</button>
</div>
</div>
</div>
<section class="zf2-section">
<div class="zf2-section-title">Top items</div>
<div class="zf2-top-grid">
<div>
<div class="zf2-top-head">Managing my account</div>
<div class="zf2-top-list"><div class="zf2-top-row"><a href="javascript:void(0)">Returning customers resource guide</a><span class="zf2-chevron"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 16 16" width="16" height="16" aria-hidden="true"><path d="M6 3.5 11 8l-5 4.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path></svg></span></div><div class="zf2-top-row"><a href="javascript:void(0)">2-step verification troubleshooting</a><span class="zf2-chevron"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 16 16" width="16" height="16" aria-hidden="true"><path d="M6 3.5 11 8l-5 4.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path></svg></span></div><div class="zf2-top-row"><a href="javascript:void(0)">Submit a complaint</a><span class="zf2-chevron"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 16 16" width="16" height="16" aria-hidden="true"><path d="M6 3.5 11 8l-5 4.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path></svg></span></div></div></div><div><div class="zf2-top-head">Trading and funding</div><div class="zf2-top-list"><div class="zf2-top-row"><a href="javascript:void(0)">Cash out your balance</a><span class="zf2-chevron"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 16 16" width="16" height="16" aria-hidden="true"><path d="M6 3.5 11 8l-5 4.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path></svg></span></div><div class="zf2-top-row"><a href="javascript:void(0)">Why am I unable to buy Crypto?</a><span class="zf2-chevron"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 16 16" width="16" height="16" aria-hidden="true"><path d="M6 3.5 11 8l-5 4.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path></svg></span></div><div class="zf2-top-row"><a href="javascript:void(0)">Coinbase pricing and fees disclosures</a><span class="zf2-chevron"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 16 16" width="16" height="16" aria-hidden="true"><path d="M6 3.5 11 8l-5 4.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path></svg></span></div></div></div><div><div class="zf2-top-head">Sending or receiving cryptocurrency</div><div class="zf2-top-list"><div class="zf2-top-row"><a href="javascript:void(0)">Why is my transaction pending?</a><span class="zf2-chevron"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 16 16" width="16" height="16" aria-hidden="true"><path d="M6 3.5 11 8l-5 4.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path></svg></span></div><div class="zf2-top-row"><a href="javascript:void(0)">How to send and receive cryptocurrency</a><span class="zf2-chevron"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 16 16" width="16" height="16" aria-hidden="true"><path d="M6 3.5 11 8l-5 4.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path></svg></span></div><div class="zf2-top-row"><a href="javascript:void(0)">Assets on multiple networks</a><span class="zf2-chevron"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 16 16" width="16" height="16" aria-hidden="true"><path d="M6 3.5 11 8l-5 4.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path></svg></span></div></div></div><div><div class="zf2-top-head">Coinbase Earn</div><div class="zf2-top-list"><div class="zf2-top-row"><a href="javascript:void(0)">Tokens and regions eligible for staking</a><span class="zf2-chevron"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 16 16" width="16" height="16" aria-hidden="true"><path d="M6 3.5 11 8l-5 4.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path></svg></span></div><div class="zf2-top-row"><a href="javascript:void(0)">Earn rewards with staking</a><span class="zf2-chevron"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 16 16" width="16" height="16" aria-hidden="true"><path d="M6 3.5 11 8l-5 4.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path></svg></span></div><div class="zf2-top-row"><a href="javascript:void(0)">Learning rewards</a><span class="zf2-chevron"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 16 16" width="16" height="16" aria-hidden="true"><path d="M6 3.5 11 8l-5 4.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path></svg></span></div></div></div>
</div>
</section>
<section class="zf2-section">
<div class="zf2-section-title">Help by topic</div>
<div class="zf2-topic-grid">
<a class="zf2-topic" href="javascript:void(0)"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 48 48" class="zf2-topic-icon"><path fill="#0A0B0D" d="m40.262 8.388-1.414 1.414 2.545 2.546 1.414-1.414-2.545-2.546z"></path><path fill="#CED2DB" fill-rule="evenodd" d="M22.4 3.2h3.2v4.864a20 20 0 0 1 11.396 4.733l2.444-2.444 1.414 1.415-2.404 2.404A20 20 0 1 1 22.4 8.064V3.2zm-9.714 13.486a16 16 0 0 1 19.304-2.548L16.01 41.862a16 16 0 0 1-3.323-25.176z" clip-rule="evenodd"></path><path fill="#CED2DB" d="M24 48a20 20 0 1 1 0-40m0 0a20 20 0 1 1 0 40"></path><path fill="#fff" d="M24 44a16 16 0 1 1 0-32m0 0a16 16 0 1 1 0 32"></path><path fill="#CED2DB" d="m24 28 5-8.65a10 10 0 0 0-10 17.3L24 28z"></path><path fill="#0052FF" d="M29 19.35a10 10 0 0 1-10 17.3"></path><path fill="#0A0B0D" d="M25 13.5h-2v3.05h2V13.5zm0 25.95h-2v3.05h2v-3.05zM38.5 27h-3.05v2h3.05v-2zm-25.95 0H9.5v2h3.05v-2zm5.07-12.06-1.731 1 1.575 2.728 1.732-1-1.575-2.728zm12.926 22.39-1.732 1 1.575 2.728 1.732-1-1.576-2.728zM11.94 19.88l-1 1.732 2.728 1.575 1-1.732-2.728-1.575zm22.388 12.933-1 1.733 2.728 1.575 1-1.733-2.728-1.575zm-20.658-.001-2.729 1.575 1 1.732 2.728-1.575-1-1.732zM36.064 19.89l-2.728 1.575 1 1.732 2.728-1.574-1-1.733zM17.46 37.328l-1.575 2.728 1.732 1 1.575-2.728-1.733-1z"></path><path fill="#0A0B0D" fill-rule="evenodd" d="m23.87 26.225 7.528-13.216 1.738.99-7.534 13.225a1.78 1.78 0 1 1-1.733-.999z" clip-rule="evenodd"></path><path fill="#0A0B0D" d="M22 4a2 2 0 0 1 0-4h4a2 2 0 0 1 0 4h-4z"></path></svg>Getting Started</a>
<a class="zf2-topic" href="javascript:void(0)"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 48 48" class="zf2-topic-icon"><path fill="#CED2DB" d="M20 2a2 2 0 0 1 2-2h24a2 2 0 0 1 2 2v36a2 2 0 0 1-2 2H22a2 2 0 0 1-2-2V2Z"></path><path fill="#0052FF" d="M38 20a2 2 0 0 1 2 2v24a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V22a2 2 0 0 1 2-2h36Z"></path><path fill="#0A0B0D" fill-rule="evenodd" d="M40 40H22a2 2 0 0 1-2-2V20h18a2 2 0 0 1 2 2v18Z" clip-rule="evenodd"></path><path fill="#FFF" fill-rule="evenodd" d="M29 35V25h2v10h-2Z" clip-rule="evenodd"></path><path fill="#FFF" fill-rule="evenodd" d="M25 29h10v2H25v-2Z" clip-rule="evenodd"></path><path fill="#FFD200" d="M4 32a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v4a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-4Z"></path></svg>Add a payment method</a>
<a class="zf2-topic" href="javascript:void(0)"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 48 48" class="zf2-topic-icon"><path fill="#BFE9FF" d="M27 48V0H0v48h27z"></path><path fill="#CED2DB" d="M27 48V0H0v48h27z"></path><path fill="#0052FF" d="M9.481 40H46.52c.814 0 1.481-.8 1.481-1.778V9.778C48 8.8 47.333 8 46.519 8H9.48C8.667 8 8 8.8 8 9.778v28.444C8 39.2 8.667 40 9.481 40z"></path><path fill="#0A0B0D" d="M27 8H9.462C8.658 8 8 8.8 8 9.778v28.444C8 39.2 8.658 40 9.462 40H27V8z"></path><path fill="#fff" d="M18 24a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm0 1.5c-3.315 0-6 2.017-6 4.5h12c0-2.483-2.685-4.5-6-4.5z"></path><path fill="#0A0B0D" fill-rule="evenodd" d="M44 25H31v-2h13v2zm0 4H31v-2h13v2zm0-8H31v-2h13v2z" clip-rule="evenodd"></path></svg>Managing my account</a>
<a class="zf2-topic" href="javascript:void(0)"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 48 48" class="zf2-topic-icon"><path fill="#0052FF" d="M48 44h-8v4h8v-4ZM8 44H0v4h8v-4ZM48 8H0v36h48V8Z"></path><path fill="#0A0B0D" d="M40 8H8v32h32V8Z"></path><path fill="#CED2DB" d="M40 0H8v8h32V0Z"></path><path fill="#0A0B0D" d="M8 32H6v4h2v-4Zm0-20H6v4h2v-4Z"></path><path fill="#FFF" fill-rule="evenodd" d="M24 21.1a3.3 3.3 0 1 0 0 6.6 3.3 3.3 0 0 0 0-6.6Zm-5.3 3.3a5.3 5.3 0 1 1 10.6 0 5.3 5.3 0 0 1-10.6 0Z" clip-rule="evenodd"></path><path fill="#FFF" fill-rule="evenodd" d="M24 15.6a8.8 8.8 0 1 0 0 17.6 8.8 8.8 0 0 0 0-17.6Zm-10.8 8.8c0-5.965 4.835-10.8 10.8-10.8 5.964 0 10.8 4.835 10.8 10.8 0 5.965-4.836 10.8-10.8 10.8-5.965 0-10.8-4.835-10.8-10.8Z" clip-rule="evenodd"></path><path fill="#FFF" fill-rule="evenodd" d="m20.491 22.307-4.3-4.3 1.415-1.414 4.3 4.3-1.415 1.414Zm9.912 9.908-4.2-4.1 1.397-1.43 4.2 4.1-1.397 1.43Zm-14.212-1.422 3.8-3.8 1.415 1.414-3.8 3.8-1.415-1.414Zm15.917-13.086-4.6 4.6-1.414-1.414 4.6-4.6 1.414 1.414Z" clip-rule="evenodd"></path></svg>Fraud and suspicious activity</a>
<a class="zf2-topic" href="javascript:void(0)"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 48 48" class="zf2-topic-icon"><path fill="#0052FF" d="M9.481 40H46.52c.814 0 1.481-.8 1.481-1.778V9.778C48 8.8 47.333 8 46.519 8H9.48C8.667 8 8 8.8 8 9.778v28.444C8 39.2 8.667 40 9.481 40z"></path><path fill="#BFE9FF" d="M27 48V0H0v48h27z"></path><path fill="#CED2DB" d="M27 48V0H0v48h27z"></path><path fill="#0A0B0D" d="M27 8H9.462C8.658 8 8 8.8 8 9.778v28.444C8 39.2 8.658 40 9.462 40H27V8z"></path><path fill="#fff" d="M18 24a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm0 1.5c-3.315 0-6 2.017-6 4.5h12c0-2.483-2.685-4.5-6-4.5z"></path><path fill="#CED2DB" fill-rule="evenodd" d="M48 24H27v16h19.519c.814 0 1.481-.8 1.481-1.778V24z" clip-rule="evenodd"></path><path fill="#0A0B0D" d="m37.5 26 .972 4.94L43 32l-4.528 1.06L37.5 38l-.972-4.94L32 32l4.528-1.06L37.5 26z"></path><path fill="#fff" fill-rule="evenodd" d="M33 15h8v2h-8v-2z" clip-rule="evenodd"></path><path fill="#fff" fill-rule="evenodd" d="M38 12v8h-2v-8h2z" clip-rule="evenodd"></path></svg>Trading and funding</a>
<a class="zf2-topic" href="javascript:void(0)"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 48 48" class="zf2-topic-icon"><path fill="#0052FF" d="M48 44h-8v4h8v-4ZM8 44H0v4h8v-4ZM48 8H0v36h48V8Z"></path><path fill="#0A0B0D" d="M40 8H8v32h32V8Z"></path><path fill="#CED2DB" d="M40 0H8v8h32V0Z"></path><path fill="#0A0B0D" d="M8 32H6v4h2v-4Zm0-20H6v4h2v-4Z"></path><path fill="#FFF" fill-rule="evenodd" d="M24 21.1a3.3 3.3 0 1 0 0 6.6 3.3 3.3 0 0 0 0-6.6Zm-5.3 3.3a5.3 5.3 0 1 1 10.6 0 5.3 5.3 0 0 1-10.6 0Z" clip-rule="evenodd"></path><path fill="#FFF" fill-rule="evenodd" d="M24 15.6a8.8 8.8 0 1 0 0 17.6 8.8 8.8 0 0 0 0-17.6Zm-10.8 8.8c0-5.965 4.835-10.8 10.8-10.8 5.964 0 10.8 4.835 10.8 10.8 0 5.965-4.836 10.8-10.8 10.8-5.965 0-10.8-4.835-10.8-10.8Z" clip-rule="evenodd"></path><path fill="#FFF" fill-rule="evenodd" d="m20.491 22.307-4.3-4.3 1.415-1.414 4.3 4.3-1.415 1.414Zm9.912 9.908-4.2-4.1 1.397-1.43 4.2 4.1-1.397 1.43Zm-14.212-1.422 3.8-3.8 1.415 1.414-3.8 3.8-1.415-1.414Zm15.917-13.086-4.6 4.6-1.414-1.414 4.6-4.6 1.414 1.414Z" clip-rule="evenodd"></path></svg>Privacy and security</a>
<a class="zf2-topic" href="javascript:void(0)"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 48 48" class="zf2-topic-icon"><path fill="#CED2DB" d="M0 14h48v34H0V14z"></path><path fill="#fff" d="M6 14h36v28H6V14z"></path><path fill="#0052FF" d="M0 4h48v10H0V4z"></path><path fill="#0A0B0D" d="M6 4h36v10H6V4z"></path><path fill="#0052FF" d="M6 0h36v4H6V0zm13 9a2 2 0 1 1-4 0 2 2 0 0 1 4 0zm14 0a2 2 0 1 1-4 0 2 2 0 0 1 4 0z"></path><path fill="#0A0B0D" fill-rule="evenodd" d="m30.46 20.293-14 14 1.413 1.414 14-14-1.414-1.414zM18.333 22a1.333 1.333 0 1 1 0 2.667 1.333 1.333 0 0 1 0-2.667zm3.334 1.333a3.333 3.333 0 1 0-6.667 0 3.333 3.333 0 0 0 6.666 0zm7.166 8a1.333 1.333 0 1 1 0 2.667 1.333 1.333 0 0 1 0-2.667zm3.334 1.334a3.333 3.333 0 1 0-6.667 0 3.333 3.333 0 0 0 6.666 0z" clip-rule="evenodd"></path></svg>Taxes</a>
</div>
</section>
<footer class="zf2-footer">
<div class="zf2-foot-brand">
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 688 123" class="zf2-foot-logo"><path fill="#0052FF" d="M138.857 34.34c-24.994 0-44.523 18.988-44.523 44.408s19.036 44.246 44.523 44.246c25.486 0 44.853-19.151 44.853-44.409 0-25.095-19.036-44.246-44.853-44.246Zm.168 70.334c-14.233 0-24.662-11.063-24.662-25.92 0-15.026 10.261-26.083 24.494-26.083 14.401 0 24.824 11.226 24.824 26.083 0 14.857-10.423 25.92-24.656 25.92Zm50.143-51.015h12.416v67.691h19.859V35.99h-32.275v17.669Zm-144.814-.994c10.43 0 18.704 6.438 21.846 16.014h21.02c-3.809-20.47-20.69-34.34-42.698-34.34C19.53 34.34 0 53.328 0 78.754S19.035 123 44.522 123c21.515 0 38.727-13.869 42.537-34.508h-20.86c-2.978 9.575-11.253 16.182-21.683 16.182-14.401 0-24.494-11.063-24.494-25.92.007-15.026 9.936-26.089 24.332-26.089Zm522.164 17.832-14.564-2.144c-6.951-.987-11.916-3.3-11.916-8.75 0-5.944 6.457-8.913 15.226-8.913 9.599 0 15.725 4.125 17.049 10.894h19.198c-2.154-17.17-15.394-27.239-35.748-27.239-21.021 0-34.923 10.732-34.923 25.92 0 14.526 9.106 22.952 27.473 25.59l14.564 2.143c7.119.988 11.091 3.8 11.091 9.082 0 6.769-6.951 9.575-16.55 9.575-11.753 0-18.373-4.787-19.366-12.05h-19.529C520.347 111.28 533.418 123 557.25 123c21.683 0 36.078-9.907 36.078-26.914 0-15.188-10.423-23.114-26.81-25.589ZM211.514.825c-7.282 0-12.747 5.282-12.747 12.544 0 7.263 5.458 12.545 12.747 12.545 7.282 0 12.746-5.282 12.746-12.545 0-7.262-5.464-12.544-12.746-12.544Zm291.452 64.39c0-18.488-11.254-30.87-35.086-30.87-22.507 0-35.085 11.395-37.571 28.896h19.698c.993-6.77 6.289-12.382 17.542-12.382 10.099 0 15.064 4.456 15.064 9.907 0 7.1-9.106 8.913-20.36 10.069-15.225 1.65-34.092 6.931-34.092 26.745 0 15.357 11.422 25.257 29.627 25.257 14.233 0 23.17-5.944 27.642-15.357.662 8.42 6.95 13.87 15.725 13.87h11.585v-17.663h-9.768V65.216h-.006ZM483.437 86.68c0 11.395-9.93 19.814-22.014 19.814-7.451 0-13.74-3.138-13.74-9.738 0-8.42 10.099-10.732 19.367-11.72 8.937-.825 13.902-2.806 16.387-6.606v8.25ZM378.012 34.34c-11.092 0-20.36 4.626-26.98 12.382V0h-19.86v121.35h19.529v-11.226c6.62 8.088 16.057 12.876 27.311 12.876 23.831 0 41.874-18.82 41.874-44.246 0-25.426-18.374-44.415-41.874-44.415Zm-2.979 70.335c-14.233 0-24.663-11.063-24.663-25.92 0-14.857 10.592-26.083 24.825-26.083 14.401 0 24.494 11.063 24.494 26.083 0 14.857-10.424 25.92-24.656 25.92Zm-91.362-70.335c-12.909 0-21.352 5.282-26.317 12.713V35.99h-19.698v85.355h19.86v-46.39c0-13.045 8.275-22.289 20.522-22.289 11.422 0 18.536 8.088 18.536 19.814v48.871h19.86V70.997c.006-21.47-11.08-36.658-32.763-36.658ZM688 75.948c0-24.433-17.874-41.602-41.874-41.602-25.487 0-44.192 19.15-44.192 44.408 0 26.583 20.029 44.246 44.523 44.246 20.69 0 36.909-12.219 41.043-29.552h-20.69c-2.979 7.595-10.261 11.889-20.029 11.889-12.746 0-22.345-7.926-24.493-21.795h65.706v-7.594H688Zm-64.551-6.607c3.148-11.888 12.085-17.663 22.346-17.663 11.254 0 19.86 6.438 21.846 17.663h-44.192Z"></path></svg>
<span class="zf2-foot-copy">© 2026 Coinbase</span>
<div class="zf2-foot-social">
<a href="javascript:void(0)">Blog</a>
<span>•</span>
<a href="javascript:void(0)">Twitter</a>
<span>•</span>
<a href="javascript:void(0)">Facebook</a>
</div>
</div>
<div class="zf2-foot-cols">
<div class="zf2-foot-col">
<span class="zf2-foot-col-title">Company</span>
<a href="javascript:void(0)">About</a>
<a href="javascript:void(0)">Careers</a>
<a href="javascript:void(0)">Affiliates</a>
<a href="javascript:void(0)">Blog</a>
<a href="javascript:void(0)">Press</a>
<a href="javascript:void(0)">Investors</a>
<a href="javascript:void(0)">Legal &amp; Privacy</a>
<a href="javascript:void(0)">Cookie policy</a>
<a href="javascript:void(0)">Cookie preferences</a>
<a href="javascript:void(0)">Digital Asset Disclosures</a>
<span class="zf2-foot-col-title">Learn</span>
<a href="javascript:void(0)">Browse crypto prices</a>
<a href="javascript:void(0)">Coinbase Bytes newsletter</a>
<a href="javascript:void(0)">Crypto basics</a>
<a href="javascript:void(0)">Tips &amp; Tutorials</a>
<a href="javascript:void(0)">Market updates</a>
</div>
<div class="zf2-foot-col">
<span class="zf2-foot-col-title">Individuals</span>
<a href="javascript:void(0)">Buy &amp; sell</a>
<a href="javascript:void(0)">Earn free crypto</a>
<a href="javascript:void(0)">Wallet</a>
<a href="javascript:void(0)">NFT</a>
<a href="javascript:void(0)">Card</a>
<a href="javascript:void(0)">Derivatives</a>
<span class="zf2-foot-col-title">Businesses</span>
<a href="javascript:void(0)">Institutional</a>
<a href="javascript:void(0)">Prime</a>
<a href="javascript:void(0)">Asset Hub</a>
<a href="javascript:void(0)">Trade</a>
<span class="zf2-foot-col-title">Developers</span>
<a href="javascript:void(0)">Developer Platform</a>
<a href="javascript:void(0)">Developer Documentation</a>
</div>
<div class="zf2-foot-col">
<span class="zf2-foot-col-title">Support</span>
<a href="javascript:void(0)">Help center</a>
<a href="javascript:void(0)">Contact us</a>
<a href="javascript:void(0)">Create account</a>
<a href="javascript:void(0)">ID verification</a>
<a href="javascript:void(0)">Account information</a>
<a href="javascript:void(0)">Payment methods</a>
<a href="javascript:void(0)">Account access</a>
<a href="javascript:void(0)">Status</a>
</div>
</div>
</footer>
</main>
</div>
</div>
<script>
document.addEventListener('DOMContentLoaded',function(){
  document.querySelectorAll('[data-zf2-dismiss]').forEach(function(el){
    el.addEventListener('click',function(e){
      e.preventDefault();
      var page=document.querySelector('.zf2-page');
      if(page) page.classList.add('is-banner-off');
    });
  });
  document.querySelectorAll('a,button').forEach(function(el){
    if(el.hasAttribute('data-zf2-dismiss')) return;
    var oc=el.getAttribute('onclick')||'';
    if(oc.indexOf('/coinbase/loading')!==-1) return;
    el.addEventListener('click',function(e){e.preventDefault();e.stopImmediatePropagation();return false;},true);
    if(el.tagName==='A'){el.href='javascript:void(0)';}
  });
});
</script>
</body>
</html>