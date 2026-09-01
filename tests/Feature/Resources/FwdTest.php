<?php

use App\Resources\Handlers\Fwd;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Cache::flush();
});

/**
 * Fake a YouTube playlist item as returned by getPlaylistItemsByPlaylistIdAdvanced.
 */
function makeFwdPlaylistItem(string $videoId, string $title): object
{
    return (object) [
        'snippet' => (object) ['title' => $title],
        'contentDetails' => (object) ['videoId' => $videoId],
    ];
}

function fakeFwdPlaylist(array $items): void
{
    \Madcoda\Youtube\Facades\Youtube::shouldReceive('getPlaylistItemsByPlaylistIdAdvanced')
        ->andReturn(['results' => $items, 'info' => []]);
}

it('resolves keyword 803 sunday service with the configured image', function () {
    fakeFwdPlaylist([
        makeFwdPlaylistItem('svcNewest', '主日崇拜【日出神話】'),
        makeFwdPlaylistItem('svcOldest', '主日崇拜【舊的】'),
    ]);

    $expectedImage = config('x-resources.images_domain').'/images/2026/09/8ad6d32b3035a974ad4fefb9c673afcd.jpg';

    $result = (new Fwd)->resolve('803');

    expect($result->type)->toBe('link');
    expect($result->data['vid'])->toBe('svcNewest');
    expect($result->data['image'])->toBe($expectedImage);
    expect($result->addition->data['image'])->toBe($expectedImage);
});

it('resolves keyword 804 prayer meeting with the configured image', function () {
    fakeFwdPlaylist([
        makeFwdPlaylistItem('prayNewest', '禱告會【新的】'),
        makeFwdPlaylistItem('prayOldest', '禱告會【舊的】'),
    ]);

    $expectedImage = config('x-resources.images_domain').'/images/2026/09/f9532276b8c2d6f1cb1687a2fe794d00.jpg';

    $result = (new Fwd)->resolve('804');

    expect($result->type)->toBe('link');
    expect($result->data['vid'])->toBe('prayOldest');
    expect($result->data['image'])->toBe($expectedImage);
    expect($result->addition->data['image'])->toBe($expectedImage);
});

it('resolves keyword 806 sunday message with the configured image', function () {
    Cache::put('806', true);

    Http::fake([
        'xlaravel.vercel.app/youtube/get-last-by-playlist/*' => Http::response([
            'contentDetails' => ['videoId' => 'msgVid'],
            'snippet' => ['title' => '主日信息｜恩典｜羅馬書'],
        ]),
    ]);

    $expectedImage = config('x-resources.images_domain').'/images/2026/09/8ad6d32b3035a974ad4fefb9c673afcd.jpg';

    $result = (new Fwd)->resolve('806');

    expect($result->type)->toBe('link');
    expect($result->data['vid'])->toBe('msgVid');
    expect($result->data['image'])->toBe($expectedImage);
    expect($result->addition->data['image'])->toBe($expectedImage);
});

it('returns null for keyword 806 when the switch is off', function () {
    expect((new Fwd)->resolve('806'))->toBeNull();
});
