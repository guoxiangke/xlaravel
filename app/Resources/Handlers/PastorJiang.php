<?php

namespace App\Resources\Handlers;

use App\Resources\Helpers\ImageHelper;
use App\Resources\ResourceResponse;
use Illuminate\Support\Facades\Http;

class PastorJiang
{
    public function getResourceList(): array
    {
        return [
            ['keyword' => '900', 'title' => '最新更新'],
        ];
    }

    public function resolve(string $keyword): ?ResourceResponse
    {
        if ($keyword == 900) {
            $who = '@jiangyongliu';
            $baseUrl = config('x-resources.r2_pub_domain').'/youtube_channels/latest_update';

            return $this->fetchVideoData($baseUrl, $who, 'streams', $keyword);
        }

        return null;
    }

    private function fetchVideoData($baseUrl, $who, $type, $keywordId): ?ResourceResponse
    {
        try {
            $url = "{$baseUrl}/{$who}_{$type}.json";

            $json = Http::get($url)->json();
            $vid = $json['id'];

            $audioData = $this->buildMediaData('music', $who, $vid, $json, $keywordId, 'audio');

            $audioData->addition = ResourceResponse::videoGuide("{$who}/{$vid}");

            return $audioData;
        } catch (\Exception $e) {
            return null;
        }
    }

    private function buildMediaData($type, $who, $vid, $json, $keywordId, $statisticsType): ResourceResponse
    {
        $isVideo = ($type === 'link');
        $urlBase = $isVideo ? config('x-resources.r2_share_video') : config('x-resources.r2_share_audio');
        $extension = $isVideo ? 'mp4' : 'm4a';

        $image = ImageHelper::youtubeThumbnail($vid);

        $data = [
            'url' => $urlBase."/{$who}/{$vid}.{$extension}",
            'title' => $json['title'],
            'description' => '江涌流牧师的频道',
            'vid' => $vid,
            'image' => $image,
        ];

        $statistics = [
            'metric' => class_basename(__CLASS__),
            'keyword' => $keywordId,
            'type' => $statisticsType,
        ];

        return $isVideo ? ResourceResponse::link($data, $statistics) : ResourceResponse::music($data, $statistics);
    }
}
