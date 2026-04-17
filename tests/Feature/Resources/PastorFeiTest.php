<?php

use Illuminate\Support\Carbon;

it('resolves keyword 805 to today PastorFei audio (UTC+8)', function () {
    Carbon::setTestNow(Carbon::parse('2026-04-17 01:00:00', 'Asia/Shanghai'));

    $expected = config('x-resources.r2_share_audio').'/boteli/260417.MP3';

    $response = $this->getJson('/resources/805');

    $response->assertOk()
        ->assertJsonPath('type', 'music')
        ->assertJsonPath('data.url', $expected)
        ->assertJsonMissingPath('data.vid')
        ->assertJsonPath('statistics.metric', 'PastorFei')
        ->assertJsonPath('statistics.keyword', '805')
        ->assertJsonPath('statistics.type', 'audio');
});

it('uses UTC+8 when server is UTC', function () {
    // 2026-04-17 23:30 UTC === 2026-04-18 07:30 Shanghai
    Carbon::setTestNow(Carbon::parse('2026-04-17 23:30:00', 'UTC'));

    $expected = config('x-resources.r2_share_audio').'/boteli/260418.MP3';

    $this->getJson('/resources/805')
        ->assertOk()
        ->assertJsonPath('data.url', $expected);
});

it('returns 404 for non-matching keywords on PastorFei', function () {
    $this->getJson('/resources/8050')->assertNotFound();
});
