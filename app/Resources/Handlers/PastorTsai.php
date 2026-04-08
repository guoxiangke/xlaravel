<?php

namespace App\Resources\Handlers;

use App\Resources\Helpers\YouTubeHelper;
use App\Resources\ResourceResponse;

class PastorTsai
{
    private const CHANNEL_NAME = 'PastorTsai';

    public function getResourceList(): array
    {
        return [
            ['keyword' => '802', 'title' => '活潑的生命 蔡金龍牧師'],
        ];
    }

    public function resolve(string $keyword): ?ResourceResponse
    {
        if ($keyword !== '802') {
            return null;
        }

        try {
            $items = YouTubeHelper::scrapeChannel(self::CHANNEL_NAME, 'videos', 1);

            if (empty($items)) {
                return null;
            }

            $response = YouTubeHelper::buildChannelResponse(
                $items[0]['videoId'],
                $items[0]['title'],
                self::CHANNEL_NAME
            );

            $videoData = ResourceResponse::link(
                $response['addition']['data'],
                $response['addition']['statistics']
            );

            $audioData = ResourceResponse::music(
                $response['data'],
                $response['statistics']
            );

            $audioData->addition = $videoData;

            return $audioData;
        } catch (\Exception $e) {
            return null;
        }
    }
}
