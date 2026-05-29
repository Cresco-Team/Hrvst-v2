<?php

test('app layout uses the build manifest path for PWA', function () {
    $content = file_get_contents(resource_path('views/app.blade.php'));

    expect($content)->toContain("href=\"{{ asset('build/manifest.webmanifest') }}\"");
    expect($content)->not->toContain('href="/manifest.webmanifest"');
});
