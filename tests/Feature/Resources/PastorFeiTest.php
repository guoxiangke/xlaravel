<?php

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Cache::flush();
});

it('resolves keyword 805 to today PastorFei audio with CSV title (UTC+8)', function () {
    Carbon::setTestNow(Carbon::parse('2026-04-17 01:00:00', 'Asia/Shanghai'));

    Http::fake([
        '*boteli/xf.csv' => Http::response(
            "时间,标题\n260417,第0课 测试标题\n260418,第1课 我灵魂兴盛身体健康\n"
        ),
    ]);

    $expected = config('x-resources.r2_share_audio').'/boteli/260417.MP3';

    $response = $this->getJson('/resources/805');

    $response->assertOk()
        ->assertJsonPath('type', 'music')
        ->assertJsonPath('data.url', $expected)
        ->assertJsonPath('data.title', '第0课 测试标题')
        ->assertJsonPath('data.description', '260417')
        ->assertJsonMissingPath('data.vid')
        ->assertJsonPath('statistics.metric', 'PastorFei')
        ->assertJsonPath('statistics.keyword', '805')
        ->assertJsonPath('statistics.type', 'audio');
});

it('uses UTC+8 when server is UTC', function () {
    // 2026-04-17 23:30 UTC === 2026-04-18 07:30 Shanghai (Saturday)
    Carbon::setTestNow(Carbon::parse('2026-04-17 23:30:00', 'UTC'));

    Http::fake([
        '*boteli/xf.csv' => Http::response(
            "时间,标题\n260418,第1课 我灵魂兴盛身体健康\n"
        ),
    ]);

    $expected = config('x-resources.r2_share_audio').'/boteli/260418.MP3';

    $this->getJson('/resources/805')
        ->assertOk()
        ->assertJsonPath('data.url', $expected)
        ->assertJsonPath('data.title', '第1课 我灵魂兴盛身体健康')
        ->assertJsonPath('data.description', '260418');
});

it('falls back to default title when CSV has no matching date', function () {
    Carbon::setTestNow(Carbon::parse('2026-04-17 01:00:00', 'Asia/Shanghai'));

    Http::fake([
        '*boteli/xf.csv' => Http::response("时间,标题\n260418,第1课 我灵魂兴盛身体健康\n"),
    ]);

    $this->getJson('/resources/805')
        ->assertOk()
        ->assertJsonPath('data.title', '伯特利每日灵修')
        ->assertJsonPath('data.description', '260417');
});

it('falls back to default title when CSV fetch fails', function () {
    Carbon::setTestNow(Carbon::parse('2026-04-17 01:00:00', 'Asia/Shanghai'));

    Http::fake([
        '*boteli/xf.csv' => Http::response('', 500),
    ]);

    $this->getJson('/resources/805')
        ->assertOk()
        ->assertJsonPath('data.title', '伯特利每日灵修')
        ->assertJsonPath('data.description', '260417');
});

it('caches CSV until end of Shanghai day', function () {
    Carbon::setTestNow(Carbon::parse('2026-04-17 10:00:00', 'Asia/Shanghai'));
    Http::fake([
        '*boteli/xf.csv' => Http::response("时间,标题\n260417,Day17\n"),
    ]);

    $this->getJson('/resources/805')->assertOk()->assertJsonPath('data.title', 'Day17');

    // Same day → serve from cache (swap fake to prove no refetch).
    Http::fake([
        '*boteli/xf.csv' => Http::response("时间,标题\n260417,Changed\n"),
    ]);
    $this->getJson('/resources/805')->assertOk()->assertJsonPath('data.title', 'Day17');

    // Verify TTL was set to end of Shanghai day (allow sub-second rounding).
    $endOfDay = Carbon::parse('2026-04-17', 'Asia/Shanghai')->endOfDay()->getTimestamp();
    $store = Cache::store()->getStore();
    $entries = (new ReflectionClass($store))->getProperty('storage')->getValue($store);
    $expiresAt = $entries['xbot.keyword.pastorfei.titles']['expiresAt'];
    expect(abs($expiresAt - $endOfDay))->toBeLessThanOrEqual(1);
});

it('returns 404 on Sunday (Asia/Shanghai)', function () {
    // 2026-04-19 is a Sunday
    Carbon::setTestNow(Carbon::parse('2026-04-19 10:00:00', 'Asia/Shanghai'));

    Http::fake([
        '*boteli/xf.csv' => Http::response("时间,标题\n260419,本不该播出\n"),
    ]);

    $this->getJson('/resources/805')->assertNotFound();
});

it('treats Saturday UTC evening as Sunday Shanghai and returns 404', function () {
    // 2026-04-18 17:00 UTC === 2026-04-19 01:00 Shanghai (Sunday)
    Carbon::setTestNow(Carbon::parse('2026-04-18 17:00:00', 'UTC'));

    Http::fake([
        '*boteli/xf.csv' => Http::response("时间,标题\n260419,本不该播出\n"),
    ]);

    $this->getJson('/resources/805')->assertNotFound();
});

it('returns 404 for non-matching keywords on PastorFei', function () {
    $this->getJson('/resources/8050')->assertNotFound();
});
