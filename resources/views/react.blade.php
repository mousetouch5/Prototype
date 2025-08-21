@php
    $indexPath = public_path('build/index.html');
    if (file_exists($indexPath)) {
        $html = file_get_contents($indexPath);
        // Fix asset paths to be absolute
        $html = str_replace('="/static/', '="/build/static/', $html);
        $html = str_replace('href="/static/', 'href="/build/static/', $html);
        $html = str_replace('="/manifest.json"', '="/build/manifest.json"', $html);
        $html = str_replace('="/favicon.ico"', '="/build/favicon.ico"', $html);
        echo $html;
    } else {
        echo '<!DOCTYPE html><html><head><title>Loading...</title></head><body><h1>React app not built. Please build the frontend.</h1></body></html>';
    }
@endphp