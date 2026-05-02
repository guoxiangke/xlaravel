<?php

use App\Resources\Handlers\Lts;
use App\Resources\ResourceResponse;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Fake API response (categorized by Lts::categorizeCourse()):
 *   专题特辑 → pos 1 = 毕业周特辑2015 (6 ep), pos 2 = 圣诞节特辑2019 (6 ep)   [name 匹配 "特辑"]
 *   启航课程 → pos 1 = 婚姻与家庭 (24 ep)                                    [name 匹配 "婚姻与家庭"]
 *   普及本科 → pos 1 = 无分类课程 (6 ep), pos 2 = 新约综览 (30 ep),
 *              pos 3 = 诗歌智慧书II (30 ep)                                  [默认分类]
 *   普及进深 → pos 1 = 基督论 (24 ep)                                        [name 匹配 "基督论"]
 *   count=0  → 过滤掉
 *
 * URL 规则：lts/{cleanCode}/{code}{ep}.mp3，cleanCode = code 去末尾数字
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

    expect($h->resolve('abc'))->toBeNull()
        ->and($h->resolve('100'))->toBeNull()
        ->and($h->resolve('600'))->toBeNull()
        ->and($h->resolve('2001'))->toBeNull()
        ->and($h->resolve('200001'))->toBeNull();
});

it('returns null for unknown position within valid category', function () {
    // 专题特辑 只有 2 门课 (pos 1–2)；pos=99 不存在
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
        ->and($result->data['content'])->not->toContain('CBI_skip');
});

it('returns 启航课程 menu for 300', function () {
    $result = (new Lts)->resolve('300');

    expect($result->type)->toBe('text')
        ->and($result->data['content'])->toContain('【启航课程】')
        ->and($result->data['content'])->toContain('【301】婚姻与家庭');
});

it('returns 普及本科 menu for 400', function () {
    $result = (new Lts)->resolve('400');

    expect($result->data['content'])
        ->toContain('【401】无分类课程')
        ->and($result->data['content'])->toContain('【402】新约综览')
        ->and($result->data['content'])->toContain('【403】诗歌智慧书II');
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
        ->and($result->data['url'])->toMatch(
            '#^https://d3ml8yyp1h3hy5\.cloudfront\.net/lts/magrc/magrc150\d{2}\.mp3$#'
        );
});

it('auto episode is within valid range', function () {
    $result = (new Lts)->resolve('201'); // 6 集
    preg_match('/magrc150(\d{2})\.mp3$/', $result->data['url'], $m);

    expect((int) $m[1])->toBeGreaterThanOrEqual(1)
        ->and((int) $m[1])->toBeLessThanOrEqual(6);
});

it('returns auto episode for two-digit position keyword 401', function () {
    // 普及本科 pos 1 = 无分类课程 (code=mavxxx0)
    $result = (new Lts)->resolve('401');

    expect($result->data['url'])->toContain('mavxxx/mavxxx0');
});

// ── 指定集数 x{pos:02}{ep:02} ────────────────────────────────────────────────

it('returns episode 1 of 专题特辑 course 1 for 20101', function () {
    $result = (new Lts)->resolve('20101');

    expect($result)->toBeInstanceOf(ResourceResponse::class)
        ->and($result->type)->toBe('music')
        ->and($result->data['url'])->toBe(
            'https://d3ml8yyp1h3hy5.cloudfront.net/lts/magrc/magrc15001.mp3'
        );
});

it('returns episode 25 for 40325 (普及本科 course 3, ep 25)', function () {
    // 普及本科 pos 3 = 诗歌智慧书II (code=mavpk0, count=30)
    $result = (new Lts)->resolve('40325');

    expect($result->data['url'])->toBe(
        'https://d3ml8yyp1h3hy5.cloudfront.net/lts/mavpk/mavpk025.mp3'
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
    // 专题特辑 pos 2 = 圣诞节特辑2019，description='节目简介'
    $result = (new Lts)->resolve('202');

    expect($result->data['description'])->toBe('节目简介');
});

it('falls back to default description when api description is empty', function () {
    // 专题特辑 pos 1 = 毕业周特辑2015，description=''
    $result = (new Lts)->resolve('201');

    expect($result->data['description'])->toContain('良友圣经学院');
});

// ── noTagName 归类 ──────────────────────────────────────────────────────────

it('places noTagName course into 普及本科 as pos 1', function () {
    // 无分类课程 不匹配任何分类规则，落入默认的「普及本科」
    $menu = (new Lts)->resolve('400');

    expect($menu->data['content'])->toContain('【401】无分类课程');
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

    // Laravel 的 Http::fake 会累加 stub，且第一个匹配胜出。
    // 这里用 swap 替换为全新的 Factory，再注册 500 响应，确保 beforeEach 的成功 stub 不再生效。
    Http::swap(new HttpFactory);
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
