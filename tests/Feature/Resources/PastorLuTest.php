<?php

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Cache::flush();
});

/**
 * Build a fake YouTube channel HTML body matching the regex used by
 * YouTubeHelper::scrapeChannel(): /vi\/([^\/]+).*?"text":"(.*?)"/.
 *
 * @param  array<int, array{0: string, 1: string}>  $items  list of [videoId, title]
 */
function makePastorLuChannelHtml(array $items): string
{
    $body = '';
    foreach ($items as [$videoId, $title]) {
        $body .= "vi/{$videoId}/hqdefault.jpg ... \"text\":\"{$title}\" ";
    }

    return $body;
}

it('resolves keyword 801 to today PastorLu daily message via scrapeChannel', function () {
    Carbon::setTestNow(Carbon::parse('2026-05-03 10:00:00', 'Asia/Shanghai'));

    Http::fake([
        'youtube.com/@pastorpaulqiankunlu618/videos' => Http::response(
            makePastorLuChannelHtml([
                ['_R0rYaNDdts', '每日圣经金句-260503-罗1:4'],
                ['0v_gLBbFGvY', '每日圣经金句-260502-弗2:13'],
                ['-NZOKFCgS70', '每日圣经金句-260501-约6:53-54'],
            ])
        ),
    ]);

    $expectedVideo = config('x-resources.r2_share_video').'/@pastorpaulqiankunlu618/_R0rYaNDdts.mp4';
    $expectedAudio = config('x-resources.r2_share_audio').'/@pastorpaulqiankunlu618/_R0rYaNDdts.m4a';

    $this->getJson('/resources/801')
        ->assertOk()
        ->assertJsonPath('type', 'music')
        ->assertJsonPath('data.url', $expectedAudio)
        ->assertJsonPath('data.title', '每日圣经金句-260503-罗1:4')
        ->assertJsonPath('data.vid', '_R0rYaNDdts')
        ->assertJsonPath('statistics.metric', 'PastorLu')
        ->assertJsonPath('statistics.keyword', '801')
        ->assertJsonPath('statistics.type', 'audio')
        ->assertJsonPath('addition.type', 'link')
        ->assertJsonPath('addition.data.url', $expectedVideo)
        ->assertJsonPath('addition.statistics.type', 'video');
});

it('falls back to the first video when no title matches today', function () {
    Carbon::setTestNow(Carbon::parse('2026-05-10 10:00:00', 'Asia/Shanghai'));

    Http::fake([
        'youtube.com/@pastorpaulqiankunlu618/videos' => Http::response(
            makePastorLuChannelHtml([
                ['_R0rYaNDdts', '每日圣经金句-260503-罗1:4'],
                ['0v_gLBbFGvY', '每日圣经金句-260502-弗2:13'],
            ])
        ),
    ]);

    $this->getJson('/resources/801')
        ->assertOk()
        ->assertJsonPath('data.vid', '_R0rYaNDdts')
        ->assertJsonPath('data.title', '每日圣经金句-260503-罗1:4');
});

it('returns 404 for keyword 801 when channel scrape returns no items', function () {
    Http::fake([
        'youtube.com/@pastorpaulqiankunlu618/videos' => Http::response(''),
    ]);

    $this->getJson('/resources/801')->assertNotFound();
});

it('caches keyword 801 result so a second call avoids YouTube', function () {
    Carbon::setTestNow(Carbon::parse('2026-05-03 10:00:00', 'Asia/Shanghai'));

    Http::fake([
        'youtube.com/@pastorpaulqiankunlu618/videos' => Http::response(
            makePastorLuChannelHtml([
                ['_R0rYaNDdts', '每日圣经金句-260503-罗1:4'],
            ])
        ),
    ]);

    $this->getJson('/resources/801')->assertOk();

    Http::fake([
        'youtube.com/@pastorpaulqiankunlu618/videos' => Http::response(
            makePastorLuChannelHtml([
                ['changedXYZ', 'changed-title'],
            ])
        ),
    ]);

    $this->getJson('/resources/801')
        ->assertOk()
        ->assertJsonPath('data.vid', '_R0rYaNDdts')
        ->assertJsonPath('data.title', '每日圣经金句-260503-罗1:4');
});
