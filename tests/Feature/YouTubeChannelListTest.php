<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

it('returns all videos with vid and title by default', function () {
    Http::fake([
        'youtube.com/@TestChannel/videos' => Http::response(
            'html vi/abc123xyz00/more "text":"Test Video" rest vi/def456xyz00/more "text":"Second Video"'
        ),
    ]);

    $response = $this->getJson('/youtube/videos/TestChannel');

    $response->assertOk()
        ->assertJsonCount(2)
        ->assertJsonPath('0.videoId', 'abc123xyz00')
        ->assertJsonPath('0.title', 'Test Video')
        ->assertJsonPath('1.videoId', 'def456xyz00')
        ->assertJsonPath('1.title', 'Second Video');
});

it('returns single video as object when limit=1', function () {
    Http::fake([
        'youtube.com/@TestChannel/videos' => Http::response(
            'html vi/abc123xyz00/more "text":"Test Video" rest vi/def456xyz00/more "text":"Second Video"'
        ),
    ]);

    $response = $this->getJson('/youtube/videos/TestChannel?limit=1');

    $response->assertOk()
        ->assertJsonPath('videoId', 'abc123xyz00')
        ->assertJsonPath('title', 'Test Video');
});

it('returns latest streams for a channel', function () {
    Http::fake([
        'youtube.com/@TestChannel/streams' => Http::response(
            'html vi/stream12345/stuff "text":"Live Stream Title"'
        ),
    ]);

    $response = $this->getJson('/youtube/streams/TestChannel');

    $response->assertOk()
        ->assertJsonPath('videoId', 'stream12345')
        ->assertJsonPath('title', 'Live Stream Title');
});

it('rejects invalid list type', function () {
    $response = $this->getJson('/youtube/invalid/TestChannel');

    $response->assertNotFound();
});

it('resolves @channelName-videos via resources route', function () {
    Http::fake([
        'youtube.com/@PastorTsai/videos' => Http::response(
            'html vi/abc123xyz00/more "text":"Test Video Title"'
        ),
    ]);

    $response = $this->getJson('/resources/@PastorTsai-videos');

    $response->assertOk()
        ->assertJsonPath('type', 'music')
        ->assertJsonPath('data.title', 'Test Video Title')
        ->assertJsonPath('addition.type', 'link')
        ->assertJsonPath('statistics.keyword', 'abc123xyz00');
});

it('resolves @channelName-streams via resources route', function () {
    Http::fake([
        'youtube.com/@TestChannel/streams' => Http::response(
            'html vi/stream12345/more "text":"Stream Title"'
        ),
    ]);

    $response = $this->getJson('/resources/@TestChannel-streams');

    $response->assertOk()
        ->assertJsonPath('type', 'music')
        ->assertJsonPath('addition.type', 'link');
});

it('downloads vids from default key', function () {
    Cache::put('youtube-vids-need-download', ['vid1', 'vid2']);

    $response = $this->getJson('/vids/download');

    $response->assertOk()->assertExactJson(['vid1', 'vid2']);
    expect(Cache::get('youtube-vids-need-download'))->toBeNull();
});

it('downloads vids from byChannel key', function () {
    Cache::put('youtube-vids-need-download-by-channel', ['vid3', 'vid4']);

    $response = $this->getJson('/vids/download/byChannel');

    $response->assertOk()->assertExactJson(['vid3', 'vid4']);
    expect(Cache::get('youtube-vids-need-download-by-channel'))->toBeNull();
});
