<?php

namespace App\Resources\Handlers;

use App\Resources\ResourceResponse;

class OurDailyBread
{
    public function getResourceList(): array
    {
        return [
            ['keyword' => 'odb', 'title' => 'Our Daily Bread'],
        ];
    }

    public function resolve(string $keyword): ?ResourceResponse
    {
        $data = [];
        if ($keyword == 'odb') {
            $now = now();
            $s1 = $now->format('Y/m');
            $s2 = $now->format('m').'-'.$now->format('d').'-'.$now->format('y');
            $url = "https://dzxuyknqkmi1e.cloudfront.net/odb/{$s1}/odb-{$s2}.mp3";
            $title = 'Our Daily Bread'.$now->format('ymd');

            return ResourceResponse::music([
                'url' => $url,
                'title' => $title,
                'description' => '来自 Our Daily Bread',
            ], [
                'metric' => class_basename(__CLASS__),
                'keyword' => $keyword,
            ]);
        }

        return null;
    }
}
