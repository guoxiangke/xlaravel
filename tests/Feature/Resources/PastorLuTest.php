<?php

use App\Resources\Helpers\ImageHelper;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Cache::flush();
});

/**
 * Build a fake YouTube channel HTML body that mimics the relevant slices of
 * YouTube's real videoRenderer JSON: a thumbnail URL containing vi/<id>/, an
 * optional duration overlay (whose "text" must NOT be picked up as the title),
 * and the actual "title":{"runs":[{"text":...}]} structure.
 *
 * @param  array<int, array{0: string, 1: string, 2?: string}>  $items  list of [videoId, title, ?durationLabel]
 */
function makePastorLuChannelHtml(array $items): string
{
    $body = '';
    foreach ($items as $item) {
        $videoId = $item[0];
        $title = $item[1];
        $duration = $item[2] ?? null;

        $body .= "vi/{$videoId}/hqdefault.jpg";
        if ($duration !== null) {
            $body .= ',"thumbnailOverlayTimeStatusRenderer":{"text":{"runs":[{"text":"'.$duration.'"}]}}';
        }
        $body .= ',"title":{"runs":[{"text":"'.$title.'"}]} ';
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
    $expectedImage = ImageHelper::thumbnail(config('x-resources.images_domain').'/images/2026/09/fff7796c70ffae08b51b2f19ad6cae61.jpg');

    $this->getJson('/resources/801')
        ->assertOk()
        ->assertJsonPath('type', 'music')
        ->assertJsonPath('data.url', $expectedAudio)
        ->assertJsonPath('data.title', '每日圣经金句-260503-罗1:4')
        ->assertJsonPath('data.vid', '_R0rYaNDdts')
        ->assertJsonPath('data.image', $expectedImage)
        ->assertJsonPath('addition.data.image', $expectedImage)
        ->assertJsonPath('statistics.metric', 'PastorLu')
        ->assertJsonPath('statistics.keyword', '801')
        ->assertJsonPath('statistics.type', 'audio')
        ->assertJsonPath('addition.type', 'link')
        ->assertJsonPath('addition.data.url', $expectedVideo)
        ->assertJsonPath('addition.statistics.type', 'video')
        // 视频 link 下挂两条嵌套文本引导（无统计）
        ->assertJsonPath('addition.addition.type', 'text')
        ->assertJsonPath('addition.addition.data.content', '@pastorpaulqiankunlu618/_R0rYaNDdts')
        ->assertJsonMissingPath('addition.addition.statistics')
        ->assertJsonPath('addition.addition.addition.type', 'text')
        ->assertJsonMissingPath('addition.addition.addition.statistics');
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

it('ignores duration overlay text and uses the real title', function () {
    Carbon::setTestNow(Carbon::parse('2026-05-03 10:00:00', 'Asia/Shanghai'));

    Http::fake([
        'youtube.com/@pastorpaulqiankunlu618/videos' => Http::response(
            makePastorLuChannelHtml([
                ['_R0rYaNDdts', '每日圣经金句-260503-罗1:4', '17:00'],
            ])
        ),
    ]);

    $this->getJson('/resources/801')
        ->assertOk()
        ->assertJsonPath('data.vid', '_R0rYaNDdts')
        ->assertJsonPath('data.title', '每日圣经金句-260503-罗1:4');
});

it('ignores subscribe-panel simpleText title and uses the real video title', function () {
    Carbon::setTestNow(Carbon::parse('2026-05-03 10:00:00', 'Asia/Shanghai'));

    // Real YouTube channel pages embed a subscribe prompt structured as
    //   "title":{"simpleText":"想要订阅此频道？"}
    // which sits between vi/<id>/ and the actual videoRenderer title. The
    // scrape regex must skip it and pick the runs-based title.
    $body = 'vi/_R0rYaNDdts/hqdefault.jpg'
        .',"engagementPanelTitleHeaderRenderer":{"title":{"simpleText":"想要订阅此频道？"}}'
        .',"title":{"runs":[{"text":"每日圣经金句-260503-罗1:4"}]} ';

    Http::fake([
        'youtube.com/@pastorpaulqiankunlu618/videos' => Http::response($body),
    ]);

    $this->getJson('/resources/801')
        ->assertOk()
        ->assertJsonPath('data.vid', '_R0rYaNDdts')
        ->assertJsonPath('data.title', '每日圣经金句-260503-罗1:4');
});

it('resolves keyword 808 to today PastorLu new testament reading', function () {
    Carbon::setTestNow(Carbon::parse('2026-05-03 10:00:00', 'Asia/Shanghai'));

    Http::fake([
        '*/luNT.json' => Http::response([
            '0503' => ['vid' => 'ntVid0503', 'title' => '带你读新约-罗马书1章'],
        ]),
    ]);

    $expectedVideo = config('x-resources.r2_share_video').'/@pastorpaulqiankunlu618/ntVid0503.mp4';
    $expectedAudio = config('x-resources.r2_share_audio').'/@pastorpaulqiankunlu618/ntVid0503.m4a';
    $expectedImage = ImageHelper::thumbnail(config('x-resources.images_domain').'/images/2026/09/085d5b0b4b5f15c4b998e61f5d35b4de.jpg');

    $this->getJson('/resources/808')
        ->assertOk()
        ->assertJsonPath('type', 'link')
        ->assertJsonPath('data.url', $expectedVideo)
        ->assertJsonPath('data.title', '带你读新约-罗马书1章')
        ->assertJsonPath('data.image', $expectedImage)
        ->assertJsonPath('statistics.metric', 'PastorLu')
        ->assertJsonPath('statistics.keyword', '808')
        ->assertJsonPath('statistics.type', 'video')
        ->assertJsonPath('addition.type', 'music')
        ->assertJsonPath('addition.data.url', $expectedAudio)
        ->assertJsonPath('addition.data.image', $expectedImage)
        ->assertJsonPath('addition.statistics.type', 'audio');
});

it('resolves keyword 808 with an md offset', function () {
    Carbon::setTestNow(Carbon::parse('2026-05-03 10:00:00', 'Asia/Shanghai'));

    Http::fake([
        '*/luNT.json' => Http::response([
            '0503' => ['vid' => 'ntVid0503', 'title' => '带你读新约-罗马书1章'],
            '0601' => ['vid' => 'ntVid0601', 'title' => '带你读新约-哥林多前书1章'],
        ]),
    ]);

    $this->getJson('/resources/8080601')
        ->assertOk()
        ->assertJsonPath('data.title', '带你读新约-哥林多前书1章');
});

it('returns 404 for keyword 808 when the day is missing from luNT.json', function () {
    Carbon::setTestNow(Carbon::parse('2026-05-03 10:00:00', 'Asia/Shanghai'));

    Http::fake([
        '*/luNT.json' => Http::response(['0601' => ['vid' => 'x', 'title' => 'y']]),
    ]);

    $this->getJson('/resources/808')->assertNotFound();
});
