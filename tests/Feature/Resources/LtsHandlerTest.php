<?php

use App\Resources\Handlers\Lts;
use App\Resources\ResourceResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Fake API response:
 *   专题特辑 → pos 1 = 毕业周特辑2015 (6 ep), pos 2 = 圣诞节特辑2019 (6 ep)
 *   启航课程 → pos 1 = 新约综览 (30 ep)
 *   普及本科 → pos 1 = 诗歌智慧书II (30 ep), pos 2 = 婚姻与家庭 (24 ep)
 *   普及进深 → pos 1 = 基督论 (24 ep)
 *   noTagName → falls into 专题特辑 (pos 3)
 *   count=0   → filtered
 */
$fakeCourses = [
    ['id' => '1',   'name' => '毕业周特辑2015', 'code' => 'magrc150', 'count' => 6,  'category' => '专题特辑', 'description' => ''],
    ['id' => '14',  'name' => '圣诞节特辑2019', 'code' => 'maxma190', 'count' => 6,  'category' => '专题特辑', 'description' => '节目简介'],
    ['id' => '50',  'name' => '无分类课程',     'code' => 'mavxxx0',  'count' => 6,  'category' => 'noTagName', 'description' => ''],
    ['id' => '91',  'name' => '新约综览',       'code' => 'mavnt0',   'count' => 30, 'category' => '启航课程', 'description' => ''],
    ['id' => '101', 'name' => '诗歌智慧书II',   'code' => 'mavpk0',   'count' => 30, 'category' => '普及本科', 'description' => ''],
    ['id' => '140', 'name' => '婚姻与家庭',     'code' => 'mavmf1',   'count' => 24, 'category' => '普及本科', 'description' => ''],
    ['id' => '47',  'name' => '基督论',         'code' => 'mavcy1',   'count' => 24, 'category' => '普及进深', 'description' => ''],
    ['id' => '162', 'name' => 'CBI_skip',       'code' => 'mavspv5',  'count' => 0,  'category' => '',         'description' => ''],
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

it('returns null for non-matching keywords', function () {
    $h = new Lts;

    expect($h->resolve('abc'))->toBeNull()   // 非数字
        ->and($h->resolve('100'))->toBeNull() // 首位 1 不在 2–5
        ->and($h->resolve('600'))->toBeNull() // 首位 6 不在 2–5
        ->and($h->resolve('2001'))->toBeNull() // 4 位不支持
        ->and($h->resolve('200001'))->toBeNull(); // 6 位不支持
});

it('returns null for unknown position within valid category', function () {
    // 专题特辑 只有 3 门课 (pos 1–3)；pos=99 不存在
    expect((new Lts)->resolve('299'))->toBeNull();
});

// ── 分类菜单 x00 ────────────────────────────────────────────────────────────

it('returns 专题特辑 menu for 200', function () {
    $result = (new Lts)->resolve('200');

    expect($result)->toBeInstanceOf(ResourceResponse::class)
        ->and($result->type)->toBe('text')
        ->and($result->data['content'])->toContain('【专题特辑】')
        ->and($result->data['content'])->toContain('【201】毕业周特辑2015')
        ->and($result->data['content'])->toContain('【202】圣诞节特辑2019')
        ->and($result->data['content'])->toContain('【203】无分类课程') // noTagName → 专题特辑 pos 3
        ->and($result->data['content'])->not->toContain('CBI_skip');  // count=0 已过滤
});

it('returns 启航课程 menu for 300', function () {
    $result = (new Lts)->resolve('300');

    expect($result->type)->toBe('text')
        ->and($result->data['content'])->toContain('【启航课程】')
        ->and($result->data['content'])->toContain('【301】新约综览');
});

it('returns 普及本科 menu for 400', function () {
    $result = (new Lts)->resolve('400');

    expect($result->data['content'])
        ->toContain('【401】诗歌智慧书II')
        ->and($result->data['content'])->toContain('【402】婚姻与家庭');
});

it('returns 普及进深 menu for 500', function () {
    $result = (new Lts)->resolve('500');

    expect($result->data['content'])->toContain('【501】基督论');
});

// ── 课程轮播 x01–x99 ────────────────────────────────────────────────────────

it('returns auto episode for course keyword 201', function () {
    $result = (new Lts)->resolve('201');

    expect($result)->toBeInstanceOf(ResourceResponse::class)
        ->and($result->type)->toBe('music')
        ->and($result->data['url'])->toMatchRegex(
            '#^https://d3ml8yyp1h3hy5\.cloudfront\.net/lts/magrc150/magrc150\d{2}\.mp3$#'
        );
});

it('auto episode is within valid range', function () {
    $result = (new Lts)->resolve('201'); // 6 集
    preg_match('/magrc150(\d{2})\.mp3$/', $result->data['url'], $m);

    expect((int) $m[1])->toBeGreaterThanOrEqual(1)
        ->and((int) $m[1])->toBeLessThanOrEqual(6);
});

it('returns auto episode for two-digit position keyword 401', function () {
    $result = (new Lts)->resolve('401');

    expect($result->data['url'])->toContain('mavpk0/mavpk0');
});

// ── 指定集数 x{pos:02}{ep:02} ────────────────────────────────────────────────

it('returns episode 1 of 专题特辑 course 1 for 20101', function () {
    $result = (new Lts)->resolve('20101');

    expect($result)->toBeInstanceOf(ResourceResponse::class)
        ->and($result->type)->toBe('music')
        ->and($result->data['url'])->toBe(
            'https://d3ml8yyp1h3hy5.cloudfront.net/lts/magrc150/magrc15001.mp3'
        );
});

it('returns episode 25 for 40125 (普及本科 course 1, ep 25)', function () {
    $result = (new Lts)->resolve('40125');

    expect($result->data['url'])->toBe(
        'https://d3ml8yyp1h3hy5.cloudfront.net/lts/mavpk0/mavpk025.mp3'
    );
});

it('returns null for episode out of range', function () {
    // 专题特辑 course 1 共 6 集，ep 07 超范围
    expect((new Lts)->resolve('20107'))->toBeNull();
});

it('returns null for pos 00', function () {
    // 20100 = 专题特辑 pos=01, ep=00 → ep < 1 → null
    expect((new Lts)->resolve('20100'))->toBeNull();
});

// ── 封面图按分类变化 ────────────────────────────────────────────────────────

it('uses ltsnop cover for 专题特辑', function () {
    expect((new Lts)->resolve('201')->data['image'])
        ->toBe('https://d3ml8yyp1h3hy5.cloudfront.net/ly/image/cover/ltsnop.jpg');
});

it('uses ltsnp cover for 启航课程', function () {
    expect((new Lts)->resolve('301')->data['image'])
        ->toBe('https://d3ml8yyp1h3hy5.cloudfront.net/ly/image/cover/ltsnp.jpg');
});

it('uses ltstpa1 cover for 普及本科', function () {
    expect((new Lts)->resolve('401')->data['image'])
        ->toBe('https://d3ml8yyp1h3hy5.cloudfront.net/ly/image/cover/ltstpa1.jpg');
});

it('uses ltstpb1 cover for 普及进深', function () {
    expect((new Lts)->resolve('501')->data['image'])
        ->toBe('https://d3ml8yyp1h3hy5.cloudfront.net/ly/image/cover/ltstpb1.jpg');
});

// ── description 回落逻辑 ────────────────────────────────────────────────────

it('uses api description when available', function () {
    // course 2 of 专题特辑 (圣诞节特辑2019) has description='节目简介'
    $result = (new Lts)->resolve('202');

    expect($result->data['description'])->toBe('节目简介');
});

it('falls back to default description when api description is empty', function () {
    // course 1 of 专题特辑 has empty description
    $result = (new Lts)->resolve('201');

    expect($result->data['description'])->toContain('良友圣经学院');
});

// ── noTagName 归类 ──────────────────────────────────────────────────────────

it('places noTagName course into 专题特辑 as pos 3', function () {
    $menu = (new Lts)->resolve('200');

    expect($menu->data['content'])->toContain('【203】无分类课程');
});

// ── 缓存行为 ────────────────────────────────────────────────────────────────

it('caches catalog after first api call', function () {
    (new Lts)->resolve('201');

    expect(Cache::has('lts_courses'))->toBeTrue();

    (new Lts)->resolve('301');
    Http::assertSentCount(1); // 第二次命中缓存
});

it('does not cache on api failure', function () {
    Cache::flush();
    Http::fake(['y.lydt.work/*' => Http::response([], 500)]);

    expect((new Lts)->resolve('201'))->toBeNull()
        ->and(Cache::has('lts_courses'))->toBeFalse();
});

// ── getResourceList ─────────────────────────────────────────────────────────

it('returns resource list with correct keyword format', function () {
    $list = (new Lts)->getResourceList();
    $keywords = array_column($list, 'keyword');

    expect($keywords)->toContain('201') // 专题特辑 pos 1
        ->and($keywords)->toContain('301') // 启航课程 pos 1
        ->and($keywords)->toContain('402') // 普及本科 pos 2
        ->and($keywords)->toContain('501') // 普及进深 pos 1
        ->and($keywords)->not->toContain('200'); // x00 是菜单，不在资源列表里
});
