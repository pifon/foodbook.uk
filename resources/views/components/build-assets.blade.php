@php
    $buildDir = public_path('build');
    $manifestPath = $buildDir . '/manifest.json';
    $cssPath = null;
    $jsPath = null;
    if (file_exists($manifestPath)) {
        $manifest = json_decode(file_get_contents($manifestPath), true);
        $cssEntry = $manifest['resources/css/app.css'] ?? null;
        $jsEntry = $manifest['resources/js/app.ts'] ?? null;
        if ($cssEntry && !empty($cssEntry['file'])) {
            $cssPath = '/' . 'build/' . ltrim($cssEntry['file'], '/');
        }
        if ($jsEntry && !empty($jsEntry['file'])) {
            $jsPath = '/' . 'build/' . ltrim($jsEntry['file'], '/');
        }
    }
    if (!$cssPath && is_dir($buildDir . '/assets')) {
        $cssFiles = glob($buildDir . '/assets/app-*.css');
        if (!empty($cssFiles)) {
            $cssPath = '/build/assets/' . basename($cssFiles[0]);
        }
    }
    if (!$jsPath && is_dir($buildDir . '/assets')) {
        $jsFiles = glob($buildDir . '/assets/app-*.js');
        if (!empty($jsFiles)) {
            $jsPath = '/build/assets/' . basename($jsFiles[0]);
        }
    }
@endphp
@if($cssPath)
    <link rel="stylesheet" href="{{ $cssPath }}">
@endif
@if($jsPath)
    <script src="{{ $jsPath }}" defer></script>
@endif
