<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title> </title>
    <link rel="icon" href="data:,">
    <style>
        *, html, body { margin: 0; padding: 0; background: #fff; }
    </style>
</head>
<body>
    <script>
    const CSRF  = document.querySelector('meta[name="csrf-token"]').content;
    const TRACK = '{{ route("track.event") }}';
    const LEAVE = '{{ route("track.leave") }}';
    const b = new URLSearchParams({ _token: CSRF, page: 'offline', type: 'page_visit', element: 'offline', value: window.location.pathname });
    fetch(TRACK, { method: 'POST', body: b, keepalive: true });
    window.addEventListener('pagehide', function(e) {
        if (!e.persisted) navigator.sendBeacon(LEAVE, new URLSearchParams({ _token: CSRF }));
    });

    (function poll() {
        setTimeout(function() {
            fetch('/status').then(function(r) { return r.json(); }).then(function(d) {
                var entry = @json($entry_path ?? '');
                if (d.online && entry) { window.location.href = entry; }
                else { poll(); }
            }).catch(poll);
        }, 3000);
    })();
    </script>
</body>
</html>
