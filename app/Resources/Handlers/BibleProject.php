<?php

namespace App\Resources\Handlers;

use App\Resources\ResourceResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

final class BibleProject
{
    public function resolve(string $keyword): mixed
    {
        // 783 bibleproject 106! =》115！
        if ($keyword === '7830') {
            $cacheKey = 'xbot.keyword.7830';
            $data = Cache::get($cacheKey, false);
            if (! $data) {
                $response = Http::get('https://bibleproject.com/locale/downloads/zhs/');
                if ($response->failed()) {
                    return null;
                }
                $html = $response->body();
                $pattern = '/<div class="intl-downloads-item-title">([^<]+)<\/div>.*?<a\s+href="([^"]+\.mp4)"[^>]*>.*?(?:<a\s+href="([^"]+\.png)"[^>]*>[^<]*<\/a>|<span class="intl-downloads-item-empty">无<\/span>)/s';

                preg_match_all($pattern, $html, $matches);
                if (empty($matches[1])) {
                    // Fallback to simpler regex if the complex one fails
                    $fallbackPattern = "/<div class=\"intl-downloads-item-title\">([^<]+)<\/div>.*?<a\s+href=\"([^\"]+\.mp4)\"/s";
                    preg_match_all($fallbackPattern, $html, $matches);
                }
                $data = $matches[2]; // Only keep the MP4 links for 7830
                Cache::put($cacheKey, $data, strtotime('+1 week') - time());
            }

            return $data;
        }

        if ($keyword === 'bibleproject' || $keyword === '783') {
            $cacheKey = 'xbot.keyword.bibileproject';
            $data = Cache::get($cacheKey, false);
            if (! $data) {
                $response = Http::get('https://bibleproject.com/locale/downloads/zhs/');
                if ($response->failed()) {
                    return null;
                }
                $html = $response->body();
                $pattern = '/<div class="intl-downloads-item-title">([^<]+)<\/div>.*?<a\s+href="([^"]+\.mp4)"[^>]*>.*?(?:<a\s+href="([^"]+\.png)"[^>]*>[^<]*<\/a>|<span class="intl-downloads-item-empty">无<\/span>)/s';

                preg_match_all($pattern, $html, $matches);
                if (empty($matches[1])) {
                    // Fallback to simpler regex if the complex one fails
                    $fallbackPattern = "/<div class=\"intl-downloads-item-title\">([^<]+)<\/div>.*?<a\s+href=\"([^\"]+\.mp4)\"/s";
                    preg_match_all($fallbackPattern, $html, $matches);
                }
                $data = $matches;
                unset($data[0]);
                Cache::put($cacheKey, $data, strtotime('+1 week') - time());
            }

            $total = count($data[1]);
            if ($total === 0) {
                return null;
            }
            $offset = (now()->format('z') + 10) % $total;

            $titles = $data[1];
            $mp4links = $data[2];
            $pnglinks = $data[3] ?? [];

            $r2ShareVideo = config('x-resources.r2_share_video', 'https://video.example.com');
            $url = $r2ShareVideo.'/thebibleproject/'.basename($mp4links[$offset]);
            $title = ($offset + 1)."/{$total} 【bibleproject】".$titles[$offset];

            $addition = ResourceResponse::music([
                'url' => $url,
                'title' => $title,
                'description' => '来自 Bible Project',
            ]);

            return ResourceResponse::link([
                'url' => $url,
                'title' => $title,
                'description' => '来自 Bible Project',
                'image' => $pnglinks[$offset] ?? '',
            ], [], $addition);
        }

        return null;
    }
}
