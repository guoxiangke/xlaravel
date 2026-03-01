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
     * 去除code中的数字前缀（用于生成目录路径）
     */
    private function getCleanCode(string $code): string
    {
        // 去除末尾的数字，如 mavoe1 -> mavoe, mavcy1 -> mavcy
        return preg_replace('/\d+$/', '', $code);
    }

    /**
     * @param  array{name: string, code: string, count: int, description: string}  $course
     */
    private function buildEpisodeResponse(int $digit, int $pos, array $course, int $episode): ResourceResponse
    {
        $ep = str_pad((string) $episode, 2, '0', STR_PAD_LEFT);
        $cleanCode = $this->getCleanCode($course['code']);

        // {cdn}/lts/{cleanCode}/{code}{ep}.mp3  ← 目录名去掉数字前缀，文件名保持原code
        $url = self::CDN_BASE.$cleanCode.'/'.$course['code'].$ep.'.mp3';

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
     * 根据课程名称智能分类（因为API返回的category都是noTagName）
     */
    private function categorizeCourse(string $name, string $code): string
    {
        // 专题特辑：特辑、培灵会、周特辑等
        if (preg_match('/特辑|培灵会|院庆|开学周|毕业周|圣诞节|复活节|春节|宣教周|关怀|探访|民间|哀伤|临终|无神论|教牧特辑/', $name)) {
            return '专题特辑';
        }
        
        // 启航课程（基础课程）：婚姻与家庭、新约浅说、旧约浅说、基要真理、基本研经法、耶稣生平、信仰与生活、事奉装备、祷告生活等
        if (preg_match('/婚姻与家庭|新约浅说|旧约浅说|基要真理|基本研经法|耶稣生平|信仰与生活|事奉装备|祷告生活|学习锦囊/', $name)) {
            return '启航课程';
        }
        
        // 普及进深：带有"论"字结尾的神学课程、高级圣经研究等
        if (preg_match('/基督论|末世论|教会论|圣经论|神论|救恩论|启示论|圣经神学|旧约神学|新约神学|释经学|释经与讲道|领袖训练|教牧学|教牧辅导|崇拜学|差传神学|进深讲道学|灵命培育|转变中的家庭牧养|认识小组教会|整全使命|金龄事工|近代神学家|灵修操练/', $name)) {
            return '普及进深';
        }
        
        // 普及本科：其余的正规课程
        return '普及本科';
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

                // 使用智能分类算法替代API的category字段
                $category = $this->categorizeCourse($item['name'], $item['code']);

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
