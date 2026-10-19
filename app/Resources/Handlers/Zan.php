<?php

namespace App\Resources\Handlers;

use App\Resources\ResourceResponse;
use Dom\HTMLDocument;
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
                    $url = config('services.zanmei.search_url')."/{$name}";
                    $response = Http::get($url);
                    if ($response->failed()) {
                        return null;
                    }
                    $dom = HTMLDocument::createFromString($response->body(), LIBXML_NOERROR);

                    $notFound = $dom->querySelector('.empty');
                    if ($notFound) {
                        return ResourceResponse::text(['content' => trim($notFound->textContent) ?: '没有找到相关歌曲']);
                    }

                    $song = $this->findMostPopularSong($dom);
                    if (! $song) {
                        return null;
                    }

                    $data = [
                        'url' => config('services.zanmei.play_url')."/{$song['id']}.mp3",
                        'title' => $song['title'],
                        'description' => $song['description'],
                    ];

                    Cache::put($cacheKey, $data, 3600);
                } catch (\Exception $e) {
                    return null;
                }
            }

            return ResourceResponse::music($data, [
                'metric' => class_basename(__CLASS__),
                'keyword' => $name,
                'type' => 'audio',
            ]);
        }

        return null;
    }

    /**
     * 总人气数值已隐藏，按 .length span 的 width 百分比选出人气最高的歌曲。
     *
     * @return array{id: string, title: string, description: string}|null
     */
    protected function findMostPopularSong(HTMLDocument $dom): ?array
    {
        $bestSong = null;
        $maxPopularity = -1.0;

        foreach ($dom->querySelectorAll('.songs table tr') as $row) {
            $links = $row->querySelectorAll('.name a');
            if ($links->length === 0 || ! preg_match('/\/song\/(\d+)\.html/', $links[0]->getAttribute('href') ?? '', $idMatch)) {
                continue;
            }

            $style = $row->querySelector('.length span')?->getAttribute('style') ?? '';
            $popularity = preg_match('/width:\s*([\d.]+)%/', $style, $widthMatch) ? (float) $widthMatch[1] : 0.0;

            if ($popularity > $maxPopularity) {
                $maxPopularity = $popularity;
                $bestSong = [
                    'id' => $idMatch[1],
                    'title' => trim($links[0]->textContent),
                    'description' => trim($links[1]?->textContent.' '.$links[2]?->textContent) ?: '来自赞美诗网',
                ];
            }
        }

        return $bestSong;
    }
}
