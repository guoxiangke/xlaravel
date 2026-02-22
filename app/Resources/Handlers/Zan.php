<?php

namespace App\Resources\Handlers;

use App\Resources\ResourceResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class Zan
{
    public function getResourceList(): array
    {
        return [];
    }

    public function resolve(string $keyword): ?ResourceResponse
    {
        $triggerKeywords = ['赞美诗网', '赞美诗歌', '赞美诗', '赞美', '赞'];
        if (Str::startsWith($keyword, $triggerKeywords)) {
            $name = str_replace(
                $triggerKeywords,
                ['', '', '', '', ''],
                $keyword
            );
            $name = trim($name);

            if (empty($name)) {
                return null;
            }

            $cacheKey = "xbot.keyword.zmsg.{$name}";
            $data = Cache::get($cacheKey);

            if (! $data) {
                try {
                    $url = "https://www.zanmei.ai/search/song/{$name}";
                    $response = Http::get($url);
                    if ($response->failed()) {
                        return null;
                    }
                    $html = $response->body();

                    // Check if empty result
                    if (strpos($html, 'class="empty"') !== false) {
                        return ResourceResponse::text(['content' => '没有找到相关歌曲']);
                    }

                    // Simple regex to find song links and metadata
                    // Looking for: <a href="/song/12345.html" class="song-name">Song Title</a>
                    if (preg_match_all('/<a[^>]*href="\/song\/(\d+)\.html"[^>]*>(.*?)<\/a>/', $html, $matches)) {
                        $id = $matches[1][0];
                        $title = trim(strip_tags($matches[2][0]));

                        $mp3 = "https://play.izanmei.net/song/p/{$id}.mp3";
                        $data = [
                            'url' => $mp3,
                            'title' => $title,
                            'description' => '来自赞美诗网',
                        ];

                        Cache::put($cacheKey, $data, 3600);
                    } else {
                        return null;
                    }
                } catch (\Exception $e) {
                    return null;
                }
            }

            return ResourceResponse::music($data);
        }

        return null;
    }
}
