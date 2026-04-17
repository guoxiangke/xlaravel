<?php

namespace App\Resources\Handlers;

use App\Resources\ResourceResponse;

class PastorFei
{
    private const KEYWORD = '805';

    public function getResourceList(): array
    {
        return [
            ['keyword' => self::KEYWORD, 'title' => '伯特利每日灵修'],
        ];
    }

    public function resolve(string $keyword): ?ResourceResponse
    {
        if ($keyword !== self::KEYWORD) {
            return null;
        }

        $date = now()->setTimezone('Asia/Shanghai')->format('ymd');
        $url = config('x-resources.r2_share_audio').'/boteli/'.$date.'.MP3';

        return ResourceResponse::music([
            'url' => $url,
            'title' => "伯特利每日灵修 {$date}",
            'description' => '伯特利每日灵修',
        ], [
            'metric' => 'PastorFei',
            'keyword' => self::KEYWORD,
            'type' => 'audio',
        ]);
    }
}
