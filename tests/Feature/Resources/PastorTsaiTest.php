<?php

use Illuminate\Support\Facades\Http;

it('resolves keyword 802 to latest PastorTsai video (lockupViewModel format)', function () {
    // PastorTsai channel currently uses the new lockupViewModel layout where
    // the title is "title":{"content":"..."}. The page also embeds a subscribe
    // prompt with "title":{"simpleText":"想要订阅此频道？"} and a far-down
    // keyboard-shortcuts dialog "title":{"runs":[{"text":"键盘快捷键"}]} —
    // neither must be picked up.
    $body = 'vi/abc123xyz00/hqdefault.jpg'
        .',"thumbnailOverlayTimeStatusRenderer":{"text":{"runs":[{"text":"17:00"}]}}'
        .',"engagementPanelTitleHeaderRenderer":{"title":{"simpleText":"想要订阅此频道？"}}'
        .',"metadata":{"lockupMetadataViewModel":{"title":{"content":"2026.04.07∣活潑的生命∣詩篇90:1-17"}}}'
        .',"title":{"runs":[{"text":"键盘快捷键"}]}';

    Http::fake([
        'youtube.com/@PastorTsai/videos' => Http::response($body),
    ]);

    $response = $this->getJson('/resources/802');

    $response->assertOk()
        ->assertJsonPath('type', 'music')
        ->assertJsonPath('data.title', '2026.04.07∣活潑的生命∣詩篇90:1-17')
        ->assertJsonPath('statistics.keyword', 'abc123xyz00')
        ->assertJsonPath('statistics.type', 'audio')
        ->assertJsonPath('addition.type', 'link')
        ->assertJsonPath('addition.statistics.type', 'video');
});

it('returns 404 for non-matching keywords', function () {
    $response = $this->getJson('/resources/8020');

    $response->assertNotFound();
});
