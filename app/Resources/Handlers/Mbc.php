<?php

namespace App\Resources\Handlers;

use App\Resources\ResourceResponse;

class Mbc
{
    public function getResourceList(): array
    {
        return [
            ['keyword' => 'mbc', 'title' => '慕安德烈每日靈修签到'],
        ];
    }

    public function resolve(string $keyword): ?ResourceResponse
    {
        if ($keyword == 'mbc') {
            $day = now()->format('md');

            return ResourceResponse::link([
                'url' => 'https://check-in-out.online/devotional',
                'title' => "慕安德烈每日靈修签到-{$day}",
                'description' => '来源：大光傳宣教福音中心',
                'image' => 'https://images.simai.life/images/2024/12/bbae251f80b40a00a7ecefeb6d3c78c7.png',
            ], [
                'metric' => 'MBC',
                'keyword' => 'MBC-CN',
                'type' => 'link',
            ]);
        }

        return null;
    }
}
