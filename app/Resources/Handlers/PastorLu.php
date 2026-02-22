<?php

namespace App\Resources\Handlers;

use App\Resources\ResourceResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class PastorLu
{
    public function getResourceList(): array
    {
        return [
            ['keyword' => '801', 'title' => '卢牧师日讲'],
            ['keyword' => '802', 'title' => '卢牧师主日信息'],
            ['keyword' => '808', 'title' => '卢牧师带你读新约'],
        ];
    }

    public function resolve(string $keyword): ?ResourceResponse
    {
        $offset = substr($keyword, 3) ?: 0;
        $baseKeyword = substr($keyword, 0, 3);

        return match ($baseKeyword) {
            '801' => $this->getDailyMessage(),
            '802' => $this->getLastSundayMessage(),
            '808' => $this->getNewTestamentReading($offset),
            default => null,
        };
    }

    private function getDailyMessage(): ?ResourceResponse
    {
        try {
            $data = $this->getDailyData();
            if (! $data) {
                return null;
            }

            $vid = $data['data']['vid'];
            $data['data']['url'] = config('x-resources.r2_share_video').'/@pastorpaulqiankunlu618/'.$vid.'.mp4';

            // Create audio version
            $m4a = config('x-resources.r2_share_audio').'/@pastorpaulqiankunlu618/'.$vid.'.m4a';
            $audioResponse = ResourceResponse::music([
                'url' => $m4a,
                'title' => $data['data']['title'],
                'description' => $data['data']['description'],
                'image' => $data['data']['image'],
                'vid' => $vid,
            ]);

            $videoResponse = ResourceResponse::link(
                $data['data'],
                [],
                $audioResponse
            );

            // Return audio first with video as addition (as per original logic)
            // But avoid circular reference by not setting videoResponse's addition
            $videoResponseForAddition = ResourceResponse::link(
                $data['data']
                // No addition to avoid circular reference
            );
            $audioResponse->addition = $videoResponseForAddition;

            return $audioResponse;

        } catch (\Exception $e) {
            return null;
        }
    }

    private function getLastSundayMessage(): ?ResourceResponse
    {
        try {
            $data = $this->getLastSundayData();
            if (! $data) {
                return null;
            }

            $vid = $data['data']['vid'];
            $data['data']['url'] = config('x-resources.r2_share_video').'/@pastorpaulqiankunlu618/'.$vid.'.mp4';

            // Create audio version
            $m4a = config('x-resources.r2_share_audio').'/@pastorpaulqiankunlu618/'.$vid.'.m4a';
            $audioResponse = ResourceResponse::music([
                'url' => $m4a,
                'title' => $data['data']['title'],
                'description' => $data['data']['description'],
                'image' => $data['data']['image'],
                'vid' => $vid,
            ]);

            $videoResponse = ResourceResponse::link(
                $data['data'],
                [],
                $audioResponse
            );

            return $videoResponse;

        } catch (\Exception $e) {
            return null;
        }
    }

    private function getNewTestamentReading(string $offset): ?ResourceResponse
    {
        try {
            $day = now()->setTimezone('Asia/Shanghai')->format('md');

            // 验证 $offset 是否为有效的月日格式
            if ($offset && strtotime("2025{$offset}") !== false) {
                $day = $offset;
            }

            $url = 'https://r2.savefamily.net/luNT.json';
            $json = Http::get($url)->json();

            if (! isset($json[$day])) {
                return null;
            }

            $vid = $json[$day]['vid'];
            $title = $json[$day]['title'];
            $image = 'https://r2.savefamily.net/uPic/2023/Amn09V.jpg';

            $mp4 = config('x-resources.r2_share_video').'/@pastorpaulqiankunlu618/'.$vid.'.mp4';

            $videoResponse = ResourceResponse::link([
                'url' => $mp4,
                'title' => $title,
                'description' => '卢牧师带你读新约 © 2026',
                'image' => $image,
                'vid' => $vid,
            ]);

            // Add audio version
            $m4a = config('x-resources.r2_share_audio').'/@pastorpaulqiankunlu618/'.$vid.'.m4a';
            $audioResponse = ResourceResponse::music([
                'url' => $m4a,
                'title' => $title,
                'description' => '卢牧师带你读新约 © 2026',
                'image' => $image,
                'vid' => $vid,
            ]);

            $videoResponse->addition = $audioResponse;

            return $videoResponse;

        } catch (\Exception $e) {
            return null;
        }
    }

    private function getDailyData(): ?array
    {
        $cacheKey = 'xbot.keyword.PastorLu';
        $data = Cache::get($cacheKey);

        if (! $data) {
            try {
                $response = Http::withHeaders([
                    'Accept-Language' => 'zh-CN,zh;q=0.9,en;q=0.8',
                ])->get('https://www.youtube.com/@pastorpaulqiankunlu618/videos');

                $html = $response->body();
                $re = '#vi/([^/]+).*?"text":"(.*?)"#';
                preg_match_all($re, $html, $matches);

                $day = now()->setTimezone('Asia/Shanghai')->format('md');

                // Find yesterday's video
                foreach ($matches[2] as $key => $value) {
                    if (Str::contains($value, $day)) {
                        $vid = $matches[1][$key];
                        $title = $value;
                        break;
                    }
                }

                if (! isset($vid)) {
                    // Fallback to first video
                    $vid = $matches[1][0] ?? null;
                    $title = $matches[2][0] ?? 'Daily Message';
                }

                if (! $vid) {
                    return null;
                }

                $image = 'https://r2.savefamily.net/uPic/2023/Amn09V.jpg';
                $data = [
                    'type' => 'link',
                    'data' => [
                        'title' => $title,
                        'description' => '路牧师日讲 © 2026',
                        'image' => $image,
                        'vid' => $vid,
                    ],
                ];

                Cache::put($cacheKey, $data, 3600); // 1 hour cache

            } catch (\Exception $e) {
                return null;
            }
        }

        return $data;
    }

    private function getLastSundayData(): ?array
    {
        $cacheKey = 'xbot.keyword.PastorLu.lastSunday';
        $data = Cache::get($cacheKey);

        if (! $data) {
            try {
                $response = Http::get('https://www.youtube.com/@pastorpaulqiankunlu618/videos');
                $html = $response->body();

                $re = '#vi/([^/]+).*?"text":"(.*?)"#';
                preg_match_all($re, $html, $matches);

                // Find Sunday message
                foreach ($matches[2] as $key => $value) {
                    if (Str::containsAll($value, ['主日信息'])) {
                        $vid = $matches[1][$key];
                        $title = $value;
                        break;
                    }
                }

                if (! isset($vid)) {
                    return null;
                }

                $image = 'https://r2.savefamily.net/uPic/2023/Amn09V.jpg';
                $data = [
                    'type' => 'link',
                    'data' => [
                        'title' => $title,
                        'description' => '路牧师主日信息 © 2026',
                        'image' => $image,
                        'vid' => $vid,
                    ],
                ];

                Cache::put($cacheKey, $data, 7200); // 2 hours cache

            } catch (\Exception $e) {
                return null;
            }
        }

        return $data;
    }
}
