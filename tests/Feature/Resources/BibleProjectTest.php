<?php

use App\Resources\Handlers\BibleProject;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Cache::flush();
});

it('can fetch bible project video for keyword 7830', function () {
    Http::fake([
        'bibleproject.com/*' => Http::response('<div class="intl-downloads-item-title">Test Video</div><a href="https://example.com/test.mp4">MP4</a><a href="https://example.com/test.png">PNG</a>', 200),
    ]);

    $handler = new BibleProject;
    $result = $handler->resolve('7830');

    expect($result)->not->toBeNull();
    expect($result)->toBeArray();
});

it('can fetch bible project data for keyword bibleproject', function () {
    config(['x-resources.r2_share_video' => 'https://r2.example.com']);
    // Mocking env via config if possible, or just ensuring it's handled.
    // The original code used env('R2_SHARE_VIDEO'), I'll refactor it to use config().

    Http::fake([
        'bibleproject.com/*' => Http::response('<div class="intl-downloads-item-title">Test Video</div><a href="https://example.com/test.mp4">MP4</a><a href="https://example.com/test.png">PNG</a>', 200),
    ]);

    $handler = new BibleProject;
    $result = $handler->resolve('bibleproject');

    expect($result)->toBeInstanceOf(\App\Resources\ResourceResponse::class);
    expect($result->type)->toBe('link');
    expect($result->data['url'])->toContain('test.mp4');
});
