<?php

use App\Resources\Handlers\Youtubes;
use App\Resources\ResourceResponse;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::flush();
});

it('resolves a youtube watch link to music + video link + two-text guide', function () {
    $vid = 'lwefPmsaSAQ';

    \Madcoda\Youtube\Facades\Youtube::shouldReceive('getVideoInfo')
        ->with($vid)
        ->andReturn((object) [
            'snippet' => (object) [
                'title' => 'Test Video',
                'description' => 'Test Description',
                'thumbnails' => (object) ['medium' => (object) ['url' => 'https://img/medium.jpg']],
            ],
        ]);

    $handler = new Youtubes;
    $result = $handler->resolve("https://www.youtube.com/watch?v={$vid}");

    expect($result)->toBeInstanceOf(ResourceResponse::class);

    // 主内容：音频音乐卡片
    expect($result->type)->toBe('music');
    expect($result->statistics['type'])->toBe('audio');

    // addition：视频 link 保留
    $video = $result->addition;
    expect($video->type)->toBe('link');
    expect($video->data['url'])->toContain("tmpshare/{$vid}.mp4");
    expect($video->statistics['type'])->toBe('video');

    // link 的 addition：第一条文本 = 视频编码，无统计
    $code = $video->addition;
    expect($code->type)->toBe('text');
    expect($code->data['content'])->toBe("tmpshare/{$vid}");
    expect($code->statistics)->toBe([]);

    // 第二条文本 = 小程序引导，无统计
    $guide = $code->addition;
    expect($guide->type)->toBe('text');
    expect($guide->data['content'])->toContain('真爱聆听');
    expect($guide->statistics)->toBe([]);
});
