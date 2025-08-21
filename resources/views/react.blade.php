@php
    $indexPath = public_path('build/index.html');
    if (file_exists($indexPath)) {
        echo file_get_contents($indexPath);
    } else {
        echo '<h1>React app not built. Please build the frontend.</h1>';
    }
@endphp