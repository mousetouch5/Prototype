<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'ClickUp Sync Dashboard') }}</title>
    
    @php
        $manifest = public_path('build/asset-manifest.json');
        $assets = file_exists($manifest) ? json_decode(file_get_contents($manifest), true) : [];
    @endphp
    
    @if(isset($assets['files']['main.css']))
        <link href="/build{{ $assets['files']['main.css'] }}" rel="stylesheet">
    @endif
</head>
<body>
    <noscript>You need to enable JavaScript to run this app.</noscript>
    <div id="root"></div>
    
    @if(isset($assets['files']['main.js']))
        <script src="/build{{ $assets['files']['main.js'] }}"></script>
    @endif
</body>
</html>