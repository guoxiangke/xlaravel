<?php

use Illuminate\Support\Facades\Http;

it('resolves keyword 802 to latest PastorTsai video', function () {
    Http::fake([
        'youtube.com/@PastorTsai/videos' => Http::response(
            'html vi/abc123xyz00/more "text":"2026.04.07∣活潑的生命∣詩篇90:1-17"'
        ),
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
