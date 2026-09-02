<?php

use App\Resources\Helpers\ImageHelper;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Cache::flush();
});

it('resolves keyword 900 to audio music with a two-text video guide addition', function () {
    Http::fake([
        '*youtube_channels/latest_update*' => Http::response([
            'id' => 'vidABC',
            'title' => '最新直播',
            'thumbnails' => [['url' => 'img-low'], ['url' => 'img-high']],
        ]),
    ]);

    $expectedAudio = config('x-resources.r2_share_audio').'/@jiangyongliu/vidABC.m4a';

    $this->getJson('/resources/900')
        ->assertOk()
        // 主内容：音频音乐卡片，带统计
        ->assertJsonPath('type', 'music')
        ->assertJsonPath('data.url', $expectedAudio)
        ->assertJsonPath('data.vid', 'vidABC')
        // 封面固定取 hqdefault 并经代理转 JPEG，不再读数据源的 thumbnails
        ->assertJsonPath('data.image', ImageHelper::youtubeThumbnail('vidABC'))
        ->assertJsonPath('statistics.metric', 'PastorJiang')
        ->assertJsonPath('statistics.keyword', '900')
        ->assertJsonPath('statistics.type', 'audio')
        // addition 第一条：频道编码文本，无统计
        ->assertJsonPath('addition.type', 'text')
        ->assertJsonPath('addition.data.content', '@jiangyongliu/vidABC')
        ->assertJsonMissingPath('addition.statistics')
        // addition 第二条：小程序观看引导文本，无统计
        ->assertJsonPath('addition.addition.type', 'text')
        ->assertJsonMissingPath('addition.addition.statistics');
});

it('includes the 真爱聆听 mini-program guidance in the second text', function () {
    Http::fake([
        '*youtube_channels/latest_update*' => Http::response([
            'id' => 'vidABC',
            'title' => '最新直播',
            'thumbnails' => [['url' => 'img']],
        ]),
    ]);

    $response = $this->getJson('/resources/900')->assertOk();

    expect($response->json('addition.addition.data.content'))
        ->toContain('真爱聆听')
        ->toContain('wpx2WE1YFqWsyOt');
});

it('no longer resolves playlist keywords like 901', function () {
    $this->getJson('/resources/901')->assertNotFound();
});

it('ignores the data source thumbnails, which may be WebP or missing', function () {
    Http::fake([
        '*youtube_channels/latest_update*' => Http::response([
            'id' => 'vidABC',
            'title' => '最新直播',
            // 数据源现在只给带 sqp 签名的地址，实际返回 WebP，微信无法显示
            'thumbnails' => [
                ['url' => 'https://i.ytimg.com/vi/vidABC/hq720.jpg?sqp=-oaymwE&rs=AOn4CL'],
            ],
        ]),
    ]);

    $image = $this->getJson('/resources/900')->assertOk()->json('data.image');

    expect($image)->not->toContain('sqp=');
    expect($image)->toContain('hqdefault.jpg');
    expect($image)->toContain('output=jpg');
});

it('still resolves 900 when the data source has no thumbnails at all', function () {
    Http::fake([
        '*youtube_channels/latest_update*' => Http::response([
            'id' => 'vidABC',
            'title' => '最新直播',
        ]),
    ]);

    $this->getJson('/resources/900')
        ->assertOk()
        ->assertJsonPath('data.image', ImageHelper::youtubeThumbnail('vidABC'));
});
