<?php

use App\Resources\Handlers\Lts;
use App\Resources\ResourceResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/** @var list<array{id: string, name: string, code: string, count: int, category: string}> */
$fakeCourses = [
    ['id' => '1',   'name' => '毕业周特辑2015', 'code' => 'magrc150', 'count' => 6,  'category' => 'noTagName'],
    ['id' => '14',  'name' => '圣诞节特辑2019', 'code' => 'maxma190', 'count' => 6,  'category' => '专题特辑'],
    ['id' => '47',  'name' => '基督论',         'code' => 'mavcy1',   'count' => 24, 'category' => '普及进深'],
    ['id' => '91',  'name' => '新约综览',       'code' => 'mavnt0',   'count' => 30, 'category' => '启航课程'],
    ['id' => '101', 'name' => '诗歌智慧书II',   'code' => 'mavpk0',   'count' => 30, 'category' => '普及本科'],
    ['id' => '140', 'name' => '婚姻与家庭',     'code' => 'mavmf1',   'count' => 24, 'category' => '普及本科'],
    ['id' => '162', 'name' => 'CBI_skip',       'code' => 'mavspv5',  'count' => 0,  'category' => ''],
];

beforeEach(function () use ($fakeCourses) {
    Cache::flush();

    Http::fake([
        'y.lydt.work/*' => Http::response([
            'data' => ['data' => $fakeCourses],
        ]),
    ]);
});

// ── 非法关键字 ──────────────────────────────────────────────────────────────

it('ignores non-900 prefix keywords', function () {
    $handler = new Lts;

    expect($handler->resolve('abc'))->toBeNull()
        ->and($handler->resolve('1'))->toBeNull()
        ->and($handler->resolve('800140'))->toBeNull()
        ->and($handler->resolve('90a1'))->toBeNull();
});

it('returns null for unknown course id', function () {
    expect((new Lts)->resolve('9009'))->toBeNull(); // course 9 不存在
});

// ── 目录菜单（分类显示） ────────────────────────────────────────────────────

it('returns categorized text menu for keyword 900', function () {
    $result = (new Lts)->resolve('900');

    expect($result)->toBeInstanceOf(ResourceResponse::class)
        ->and($result->type)->toBe('text');

    $content = $result->data['content'];

    // 分类标题按顺序出现
    expect($content)->toContain('【专题特辑】')
        ->and($content)->toContain('【启航课程】')
        ->and($content)->toContain('【普及本科】')
        ->and($content)->toContain('【普及进深】');

    // noTagName 归入专题特辑，出现在专题特辑区块之后
    expect(strpos($content, '【专题特辑】'))->toBeLessThan(strpos($content, '毕业周特辑2015'));

    // count=0 已过滤
    expect($content)->not->toContain('CBI_skip');
});

it('menu shows courses under correct category', function () {
    $result = (new Lts)->resolve('900');
    $content = $result->data['content'];

    // 普及本科包含婚姻与家庭
    $pbPos = strpos($content, '【普及本科】');
    $mhPos = strpos($content, '婚姻与家庭');

    expect($pbPos)->toBeLessThan($mhPos);
});

it('menu category order matches 专题特辑→启航→普及本科→普及进深', function () {
    $result = (new Lts)->resolve('900');
    $content = $result->data['content'];

    $pos = [
        strpos($content, '【专题特辑】'),
        strpos($content, '【启航课程】'),
        strpos($content, '【普及本科】'),
        strpos($content, '【普及进深】'),
    ];

    expect($pos[0])->toBeLessThan($pos[1])
        ->and($pos[1])->toBeLessThan($pos[2])
        ->and($pos[2])->toBeLessThan($pos[3]);
});

// ── 封面图按分类变化 ────────────────────────────────────────────────────────

it('uses 专题特辑 cover for noTagName course', function () {
    $result = (new Lts)->resolve('9001'); // course 1 → noTagName → 专题特辑

    expect($result->data['image'])
        ->toBe('https://d3ml8yyp1h3hy5.cloudfront.net/ly/image/cover/ltsnop.jpg');
});

it('uses 启航课程 cover for 启航课程 category', function () {
    $result = (new Lts)->resolve('90091'); // course 91 → 启航课程

    expect($result->data['image'])
        ->toBe('https://d3ml8yyp1h3hy5.cloudfront.net/ly/image/cover/ltsnp.jpg');
});

it('uses 普及本科 cover for 普及本科 category', function () {
    $result = (new Lts)->resolve('900140'); // course 140 → 普及本科

    expect($result->data['image'])
        ->toBe('https://d3ml8yyp1h3hy5.cloudfront.net/ly/image/cover/ltstpa1.jpg');
});

it('uses 普及进深 cover for 普及进深 category', function () {
    $result = (new Lts)->resolve('90047'); // course 47 → 普及进深

    expect($result->data['image'])
        ->toBe('https://d3ml8yyp1h3hy5.cloudfront.net/ly/image/cover/ltstpb1.jpg');
});

// ── 按日期轮播 ──────────────────────────────────────────────────────────────

it('returns auto episode for 4-char keyword (single-digit course)', function () {
    $result = (new Lts)->resolve('9001');

    expect($result)->toBeInstanceOf(ResourceResponse::class)
        ->and($result->type)->toBe('music')
        ->and($result->data['url'])->toStartWith('https://d3ml8yyp1h3hy5.cloudfront.net/lts/magrc150')
        ->and($result->data['url'])->toEndWith('.mp3');
});

it('returns auto episode for 5-char keyword (two-digit course)', function () {
    $result = (new Lts)->resolve('90014');

    expect($result)->toBeInstanceOf(ResourceResponse::class)
        ->and($result->data['url'])->toContain('maxma190');
});

it('auto episode stays within course episode count', function () {
    $result = (new Lts)->resolve('9001'); // course 1 共 6 集
    preg_match('/magrc150(\d{2})\.mp3$/', $result->data['url'], $m);

    expect((int) $m[1])->toBeGreaterThanOrEqual(1)
        ->and((int) $m[1])->toBeLessThanOrEqual(6);
});

// ── 指定集数 ────────────────────────────────────────────────────────────────

it('returns episode 1 for keyword 900101', function () {
    $result = (new Lts)->resolve('900101');

    expect($result)->toBeInstanceOf(ResourceResponse::class)
        ->and($result->type)->toBe('music')
        ->and($result->data['url'])->toBe('https://d3ml8yyp1h3hy5.cloudfront.net/lts/magrc15001.mp3');
});

it('returns episode 6 for keyword 900106 (last episode of course 1)', function () {
    $result = (new Lts)->resolve('900106');

    expect($result->data['url'])
        ->toBe('https://d3ml8yyp1h3hy5.cloudfront.net/lts/magrc15006.mp3');
});

it('returns null for episode out of range', function () {
    // course 1 共 6 集；ep=07 超范围，回落到 course 107（不存在）→ null
    expect((new Lts)->resolve('900107'))->toBeNull();
});

// ── 3 位 rest 歧义解析 ──────────────────────────────────────────────────────

it('resolves 6-char keyword to 3-digit course auto when episode parse fails', function () {
    // rest='140': courseId=1, ep=40 超出课程 1 的集数(6) → 无效；回落 courseId=140 → 婚姻与家庭
    $result = (new Lts)->resolve('900140');

    expect($result)->toBeInstanceOf(ResourceResponse::class)
        ->and($result->data['url'])->toContain('mavmf1');
});

it('returns episode 30 of course 101 for keyword 9010130', function () {
    $result = (new Lts)->resolve('9010130');

    expect($result->data['url'])
        ->toBe('https://d3ml8yyp1h3hy5.cloudfront.net/lts/mavpk030.mp3');
});

// ── 缓存行为 ────────────────────────────────────────────────────────────────

it('caches api response for the rest of the day', function () {
    (new Lts)->resolve('9001');

    expect(Cache::has('lts_courses'))->toBeTrue();

    (new Lts)->resolve('90014');
    Http::assertSentCount(1); // 第二次命中缓存，API 只调用过 1 次
});

it('stores category in cache', function () {
    (new Lts)->resolve('9001');

    $cached = Cache::get('lts_courses');

    expect($cached[1]['category'])->toBe('专题特辑')  // noTagName → 专题特辑
        ->and($cached[91]['category'])->toBe('启航课程')
        ->and($cached[140]['category'])->toBe('普及本科');
});

it('filters out zero-count courses from cache', function () {
    (new Lts)->resolve('900');

    $cached = Cache::get('lts_courses');

    expect($cached)->not->toHaveKey(162);
});

it('does not cache on api failure', function () {
    Cache::flush();
    Http::fake(['y.lydt.work/*' => Http::response([], 500)]);

    expect((new Lts)->resolve('9001'))->toBeNull()
        ->and(Cache::has('lts_courses'))->toBeFalse();
});

// ── getResourceList ─────────────────────────────────────────────────────────

it('returns resource list with correct keyword format', function () {
    $list = (new Lts)->getResourceList();
    $keywords = array_column($list, 'keyword');

    expect($keywords)->toContain('9001')
        ->and($keywords)->toContain('90014')
        ->and($keywords)->toContain('900140')
        ->and($keywords)->not->toContain('900162'); // count=0 过滤
});
