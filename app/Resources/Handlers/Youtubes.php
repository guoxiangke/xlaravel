<?php

namespace App\Resources\Handlers;

use App\Resources\Helpers\YouTubeHelper;
use App\Resources\ResourceResponse;
use Illuminate\Support\Facades\Cache;

class Youtubes
{
    public function getResourceList(): array
    {
        return [];
    }

    public function resolve(string $keyword): ?ResourceResponse
    {
        if (preg_match('/(?:youtu\.be\/|youtube\.com\/(?:embed\/|v\/|watch\?v=|watch\?.+&v=|video\/|shorts\/|live\/))([\w-]{11,})/', $keyword, $matches)) {
            $vid = $matches[1];

            try {
                $videoInfo = YouTubeHelper::getVideoInfo($vid);
                $title = $videoInfo->snippet->title;
                $description = $videoInfo->snippet->description;
                $image = "https://i.ytimg.com/vi/{$vid}/maxresdefault.jpg";
                $thumbnails = $videoInfo->snippet->thumbnails->medium->url ?? $image;

                $mp4 = config('x-resources.r2_share_video')."/tmpshare/{$vid}.mp4";
                $mp3 = config('x-resources.r2_share_audio')."/tmpshare/{$vid}.m4a";

                $videoData = ResourceResponse::link([
                    'url' => $mp4,
                    'title' => $title,
                    'description' => $description,
                    'image' => $thumbnails,
                ], [
                    'metric' => 'youtube',
                    'keyword' => $vid,
                    'type' => 'video',
                ]);

                $audioData = ResourceResponse::music([
                    'url' => $mp3,
                    'title' => $title,
                    'description' => $description,
                    'image' => $thumbnails,
                ], [
                    'metric' => 'youtube',
                    'keyword' => $vid,
                    'type' => 'audio',
                ]);

                $audioData->addition = $videoData;

                $key = 'youtube-vids-need-download';
                $value = Cache::get($key, []);
                array_unshift($value, $vid);
                Cache::put($key, array_unique($value));

                return $audioData;
            } catch (\Exception $e) {
                return null;
            }
        }

        return null;
    }
}
