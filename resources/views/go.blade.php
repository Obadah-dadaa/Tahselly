<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Tahselly</title>
    <noscript><meta http-equiv="refresh" content="0;url={{ $target }}"></noscript>
    <style>body{margin:0;display:grid;place-items:center;min-height:100vh;font-family:system-ui,sans-serif;color:#64748b;background:#f8fafc}</style>
</head>
<body>
    <a href="{{ $target }}">Opening tahselly.com…</a>
    <script>
        const go = () => location.replace(@json($target, JSON_UNESCAPED_SLASHES));
        fetch(@json(route('go.hit', $ref), JSON_UNESCAPED_SLASHES), { credentials: 'same-origin' }).finally(go);
        setTimeout(go, 1500);
    </script>
</body>
</html>
