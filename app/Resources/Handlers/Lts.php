<?php

namespace App\Resources\Handlers;

use App\Resources\ResourceResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class Lts
{
    private const CDN_BASE = 'https://d3ml8yyp1h3hy5.cloudfront.net/lts/';

    private const COVER_DOMAIN = 'https://d3ml8yyp1h3hy5.cloudfront.net';

    private const API_URL = 'https://y.lydt.work/graphql';

    private const CACHE_KEY = 'lts_courses';

    private const GQL_QUERY = '{ data: lts_metas { id name code count category description } }';

    /**
     * 分类首位数字 → 分类名称
     * 关键字格式：x00 = 菜单, x01–x99 = 课程(轮播), x{pos:02}{ep:02} = 指定集
     */
    private const DIGIT_TO_CATEGORY = [
        2 => '专题特辑',
        3 => '启航课程',
        4 => '普及本科',
        5 => '普及进深',
    ];

    /** 分类 → 封面图 code */
    private const CATEGORY_COVERS = [
        '专题特辑' => 'ltsnop',
        '启航课程' => 'ltsnp',
        '普及本科' => 'ltstpa1',
        '普及进深' => 'ltstpb1',
    ];

    /**
     * @return list<array{keyword: string, title: string}>
     */
    public function getResourceList(): array
    {
        $list = [];
        $catalog = $this->getCatalog();

        foreach (self::DIGIT_TO_CATEGORY as $digit => $category) {
            $courses = $catalog[$category] ?? [];

            foreach ($courses as $pos => $course) {
                $list[] = [
                    'keyword' => $digit.str_pad((string) $pos, 2, '0', STR_PAD_LEFT),
                    'title' => $course['name'],
                ];
            }
        }

        return $list;
    }

    public function resolve(string $keyword): ?ResourceResponse
    {
        $len = strlen($keyword);

        // 只处理 3 位或 5 位纯数字，首位必须是 2–5
        if (! ctype_digit($keyword) || ! in_array($len, [3, 5], true)) {
            return null;
        }

        $digit = (int) $keyword[0];
        $category = self::DIGIT_TO_CATEGORY[$digit] ?? null;

        if ($category === null) {
            return null;
        }

        $catalog = $this->getCatalog();
        $courses = $catalog[$category] ?? [];

        // ── 3 位：x00 = 菜单，x01–x99 = 课程轮播 ──────────────────────────
        if ($len === 3) {
            $pos = (int) substr($keyword, 1);

            if ($pos === 0) {
                return $this->buildCategoryMenu($digit, $category, $courses);
            }

            $course = $courses[$pos] ?? null;

            if ($course === null) {
                return null;
            }

            $episode = (date('z') % $course['count']) + 1;

            return $this->buildEpisodeResponse($digit, $pos, $course, $episode);
        }

        // ── 5 位：x{pos:02}{ep:02} = 指定集 ────────────────────────────────
        $pos = (int) substr($keyword, 1, 2);
        $episode = (int) substr($keyword, 3, 2);

        if ($pos < 1 || $episode < 1) {
            return null;
        }

        $course = $courses[$pos] ?? null;

        if ($course === null || $episode > $course['count']) {
            return null;
        }

        return $this->buildEpisodeResponse($digit, $pos, $course, $episode);
    }

    /**
     * @param  array<int, array{name: string, code: string, count: int, description: string}>  $courses
     */
    private function buildCategoryMenu(int $digit, string $category, array $courses): ResourceResponse
    {
        $content = "=====【{$category}】=====\n";

        foreach ($courses as $pos => $course) {
            $key = $digit.str_pad((string) $pos, 2, '0', STR_PAD_LEFT);
            $content .= "【{$key}】{$course['name']}（{$course['count']}集）\n";
        }

        return ResourceResponse::text(['content' => trim($content)]);
    }

    /**
     * @param  array{name: string, code: string, count: int, description: string}  $course
     */
    private function buildEpisodeResponse(int $digit, int $pos, array $course, int $episode): ResourceResponse
    {
        $ep = str_pad((string) $episode, 2, '0', STR_PAD_LEFT);

        // {cdn}/lts/{code}/{code}{ep}.mp3  ← 与 Route /storage/ly/audio/{code}/{day}.mp3 对应
        $url = self::CDN_BASE.$course['code'].'/'.$course['code'].$ep.'.mp3';

        $coverCode = self::CATEGORY_COVERS[self::DIGIT_TO_CATEGORY[$digit]] ?? 'ltsnop';
        $image = self::COVER_DOMAIN.'/ly/image/cover/'.$coverCode.'.jpg';

        $key = $digit.str_pad((string) $pos, 2, '0', STR_PAD_LEFT);
        $description = $course['description'] !== ''
            ? $course['description']
            : "良友圣经学院 · 第{$episode}集，共{$course['count']}集";

        return ResourceResponse::music([
            'url' => $url,
            'title' => "【{$key}】{$course['name']} {$ep}",
            'description' => $description,
            'image' => $image,
        ]);
    }

    /**
     * 从 GraphQL API 拉取课程，按分类分组并分配位置编号（1–99）。
     * 结果缓存至当天结束；API 失败返回空数组，不缓存。
     *
     * @return array<string, array<int, array{name: string, code: string, count: int, description: string}>>
     */
    private function getCatalog(): array
    {
        $cached = Cache::get(self::CACHE_KEY);

        if ($cached !== null) {
            return $cached;
        }

        try {
            $response = Http::asJson()->post(self::API_URL, [
                'query' => self::GQL_QUERY,
            ]);

            $items = $response->json('data.data', []);
            $validCategories = array_values(self::DIGIT_TO_CATEGORY);

            // 先按分类分桶（保持 API 返回顺序）
            $buckets = array_fill_keys($validCategories, []);

            foreach ($items as $item) {
                $count = (int) $item['count'];

                if ($count <= 0) {
                    continue;
                }

                $raw = $item['category'] ?? '';
                $category = in_array($raw, $validCategories, true) ? $raw : '专题特辑';

                if (count($buckets[$category]) >= 99) {
                    continue; // 每分类最多 99 门
                }

                $buckets[$category][] = [
                    'name' => $item['name'],
                    'code' => $item['code'],
                    'count' => $count,
                    'description' => $item['description'] ?? '',
                ];
            }

            // 重新索引为位置编号 1–N
            $catalog = [];

            foreach ($buckets as $category => $courses) {
                $catalog[$category] = [];

                foreach (array_values($courses) as $i => $course) {
                    $catalog[$category][$i + 1] = $course;
                }
            }

            $isEmpty = array_reduce($catalog, fn ($carry, $c) => $carry && empty($c), true);

            if (! $isEmpty) {
                Cache::put(self::CACHE_KEY, $catalog, now()->endOfDay());
            }

            return $catalog;
        } catch (\Exception) {
            return [];
        }
    }
}
