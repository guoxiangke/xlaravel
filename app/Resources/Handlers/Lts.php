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

    private const GQL_QUERY = '{ data: lts_metas { id name code count category } }';

    /** 分类顺序（决定菜单排列与封面图） */
    private const CATEGORY_ORDER = ['专题特辑', '启航课程', '普及本科', '普及进深'];

    /** 各分类对应封面图片的 code */
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
        return array_values(array_map(
            fn (array $course) => [
                'keyword' => '900'.$course['id'],
                'title' => $course['name'],
            ],
            $this->getCourses()
        ));
    }

    public function resolve(string $keyword): ?ResourceResponse
    {
        if (! str_starts_with($keyword, '900')) {
            return null;
        }

        $rest = substr($keyword, 3);

        // '900' 单独输入 → 显示课程目录
        if ($rest === '') {
            return $this->getCourseMenu();
        }

        if (! ctype_digit($rest)) {
            return null;
        }

        $courses = $this->getCourses();
        $len = strlen($rest);

        // 3 位及以上：优先尝试「课程ID + 2位集数」格式
        // 例：900101 → courseId=1, ep=01；900140 → courseId=1, ep=40(超出范围) → 跳过
        if ($len >= 3) {
            $courseId = (int) substr($rest, 0, $len - 2);
            $episode = (int) substr($rest, -2);

            if ($courseId >= 1 && $episode >= 1) {
                $course = $courses[$courseId] ?? null;

                if ($course !== null && $episode <= $course['count']) {
                    return $this->buildEpisodeResponse($courseId, $course, $episode);
                }
            }
        }

        // 整段数字作为课程ID → 按日期轮播集数（day-of-year 取模）
        $courseId = (int) $rest;
        $course = $courses[$courseId] ?? null;

        if ($course !== null && $course['count'] > 0) {
            $episode = (date('z') % $course['count']) + 1;

            return $this->buildEpisodeResponse($courseId, $course, $episode);
        }

        return null;
    }

    private function getCourseMenu(): ResourceResponse
    {
        $grouped = $this->groupByCategory($this->getCourses());
        $content = '';

        foreach (self::CATEGORY_ORDER as $category) {
            $courses = $grouped[$category] ?? [];

            if (empty($courses)) {
                continue;
            }

            $content .= "=====【{$category}】=====\n";

            foreach ($courses as $id => $course) {
                $content .= "【900{$id}】{$course['name']}（{$course['count']}集）\n";
            }
        }

        return ResourceResponse::text(['content' => trim($content)]);
    }

    /**
     * 按分类顺序分组，noTagName 归入「专题特辑」。
     *
     * @param  array<int, array{id: int, name: string, code: string, count: int, category: string}>  $courses
     * @return array<string, array<int, array{id: int, name: string, code: string, count: int, category: string}>>
     */
    private function groupByCategory(array $courses): array
    {
        $grouped = array_fill_keys(self::CATEGORY_ORDER, []);

        foreach ($courses as $id => $course) {
            $category = in_array($course['category'], self::CATEGORY_ORDER, true)
                ? $course['category']
                : '专题特辑';

            $grouped[$category][$id] = $course;
        }

        return $grouped;
    }

    /**
     * 从 GraphQL API 拉取课程列表，结果缓存至当天结束（次日自动清空）。
     * API 失败时返回空数组，不写入缓存（下次请求重试）。
     *
     * @return array<int, array{id: int, name: string, code: string, count: int, category: string}>
     */
    private function getCourses(): array
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
            $courses = [];

            foreach ($items as $item) {
                $count = (int) $item['count'];

                if ($count <= 0) {
                    continue;
                }

                $id = (int) $item['id'];
                $rawCategory = $item['category'] ?? '';

                // noTagName 或空值归入「专题特辑」
                $category = in_array($rawCategory, self::CATEGORY_ORDER, true)
                    ? $rawCategory
                    : '专题特辑';

                $courses[$id] = [
                    'id' => $id,
                    'name' => $item['name'],
                    'code' => $item['code'],
                    'count' => $count,
                    'category' => $category,
                ];
            }

            if (! empty($courses)) {
                Cache::put(self::CACHE_KEY, $courses, now()->endOfDay());
            }

            return $courses;
        } catch (\Exception) {
            return [];
        }
    }

    /**
     * @param  array{id: int, name: string, code: string, count: int, category: string}  $course
     */
    private function buildEpisodeResponse(int $courseId, array $course, int $episode): ResourceResponse
    {
        $ep = str_pad((string) $episode, 2, '0', STR_PAD_LEFT);
        $url = self::CDN_BASE.$course['code'].$ep.'.mp3';
        $coverCode = self::CATEGORY_COVERS[$course['category']] ?? self::CATEGORY_COVERS['专题特辑'];
        $image = self::COVER_DOMAIN.'/ly/image/cover/'.$coverCode.'.jpg';

        return ResourceResponse::music([
            'url' => $url,
            'title' => "【{$courseId}】{$course['name']} {$ep}",
            'description' => "良友圣经学院 · 第{$episode}集，共{$course['count']}集",
            'image' => $image,
        ]);
    }
}
