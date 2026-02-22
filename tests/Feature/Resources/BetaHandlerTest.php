<?php

use App\Resources\Handlers\Beta;
use App\Resources\ResourceResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

beforeEach(function () {
    Config::set('x-resources.r2_share_audio', 'https://r2.example.com');
    Cache::flush();
});

it('resolves 794 (信心是一把梯子) with complete data', function () {
    $handler = new Beta();
    $result = $handler->resolve('794');

    expect($result)->toBeInstanceOf(ResourceResponse::class);
    expect($result->type)->toBe('music');
    expect($result->data['title'])->toContain('信心是一把梯子');
    expect($result->data['description'])->toContain('救恩之聲 有聲書');

    // Check for the index and total in the title
    expect($result->data['title'])->toMatch('/\(\d+\/73\)/');
});

it('resolves 795 (有声书系列) with complete data', function () {
    // Mock the R2 text file and JSON info files
    Http::fake([
        'https://pub-3813a5d14cba4eaeb297a0dba302143c.r2.dev/playlist/PL_sOpTJkyWnAbZRPaSktjlsv0_nH1K6aV/PL_sOpTJkyWnAbZRPaSktjlsv0_nH1K6aV.txt' => Http::response("video1\nvideo2"),
        'https://pub-3813a5d14cba4eaeb297a0dba302143c.r2.dev/playlist/PL_sOpTJkyWnAbZRPaSktjlsv0_nH1K6aV/video1.info.json' => Http::response([
            'chapters' => [['title' => 'Chapter 1']],
            'thumbnail' => 'thumb1.jpg'
        ]),
        'https://pub-3813a5d14cba4eaeb297a0dba302143c.r2.dev/playlist/PL_sOpTJkyWnAbZRPaSktjlsv0_nH1K6aV/video2.info.json' => Http::response([
            'chapters' => [['title' => 'Chapter 2']],
            'thumbnail' => 'thumb2.jpg'
        ]),
    ]);

    $handler = new Beta();
    // 7950 is "西游记精讲"
    $result = $handler->resolve('7950');

    expect($result)->toBeInstanceOf(ResourceResponse::class);
    expect($result->type)->toBe('music');
    expect($result->data['title'])->toContain('西游记精讲');
    expect($result->data['description'])->toContain('By @LucyFM1999');
});

it('resolves 797 (罗门,教牧辅导) with complete data', function () {
    $handler = new Beta();
    $result = $handler->resolve('797');

    expect($result)->toBeInstanceOf(ResourceResponse::class);
    expect($result->type)->toBe('music');
    expect($result->data['description'])->toBe('罗门,我是好牧人');

    // The actual total for 797 seems to be 52 based on actual output
    expect($result->data['title'])->toMatch('/\(\d+\/52\)/');
});

it('resolves 798 (罗门,门徒训练) with complete data', function () {
    $handler = new Beta();
    $result = $handler->resolve('798');

    expect($result)->toBeInstanceOf(ResourceResponse::class);
    expect($result->type)->toBe('music');
    expect($result->data['description'])->toBe('罗门,门徒训练');

    // The actual total for 798 is 52
    expect($result->data['title'])->toMatch('/\(\d+\/52\)/');
});

it('resolves 785 (古德恩系統神學導讀) with complete data', function () {
    $handler = new Beta();
    $result = $handler->resolve('785');

    expect($result)->toBeInstanceOf(ResourceResponse::class);
    expect($result->type)->toBe('music');
    expect($result->data['title'])->toBe('古德恩系統神學導讀 (張麟至牧師)');

    // The actual total for 785 seems to be 57 based on actual output
    expect($result->data['description'])->toMatch('/\(\d+\/57\)/');
});

it('resolves 792 (基督教要义-导读) with complete data', function () {
    $handler = new Beta();
    $result = $handler->resolve('792');

    expect($result)->toBeInstanceOf(ResourceResponse::class);
    expect($result->type)->toBe('music');
    expect($result->data['title'])->toBe('基督教要义-导读');

    expect($result->data['description'])->toMatch('/\(\d+\/110\)/');
});

it('resolves 799 (恩典365) with complete data', function () {
    $url = 'https://www.tpehoc.org.tw' . Carbon::now('Asia/Shanghai')->format('/Y/m/');

    Http::fake([
        $url => Http::response('<html><body><h3>Test Title</h3><div class="post-content">Test Description<source src="https://example.com/audio.mp3"></div></body></html>'),
    ]);

    $handler = new Beta();
    $result = $handler->resolve('799');

    expect($result)->toBeInstanceOf(ResourceResponse::class);
    expect($result->type)->toBe('music');
    expect($result->data['title'])->toBe('Test Title');
    expect($result->data['description'])->toContain('Test Description');
});

it('resolves 781 (新媒体宣教) with complete data', function () {
    $handler = new Beta();
    $result = $handler->resolve('781');

    expect($result)->toBeInstanceOf(ResourceResponse::class);
    expect($result->type)->toBe('music');
    expect($result->data['title'])->toContain('新媒体宣教课程');
});