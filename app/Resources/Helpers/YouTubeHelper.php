<?php

namespace App\Resources\Helpers;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Madcoda\Youtube\Facades\Youtube;

class YouTubeHelper
{
    /**
     * Get all items from a YouTube playlist
     * Based on https://github.com/madcoda/php-youtube-api/pull/54
     */
    public static function getAllItemsByPlaylistId(string $playlistId): Collection
    {
        $playlistItems = [];

        $params = [
            'playlistId' => $playlistId,
            'part' => 'id, snippet, contentDetails, status',
        ];

        do {
            $raw = Youtube::getPlaylistItemsByPlaylistIdAdvanced($params, true);

            if ($raw['results'] !== false) {
                $playlistItems = array_merge($playlistItems, $raw['results']);
            }

            $params['pageToken'] = $raw['info']['nextPageToken'] ?? null;
        } while ($params['pageToken'] !== null);

        return collect($playlistItems)->filter(function ($item) {
            return $item->snippet->title != 'Private video';
        });
    }

    /**
     * Get the latest video from a playlist
     */
    public static function getLatestFromPlaylist(string $playlistId): ?object
    {
        $items = self::getAllItemsByPlaylistId($playlistId);

        return $items->first();
    }

    /**
     * Get the oldest video from a playlist
     */
    public static function getOldestFromPlaylist(string $playlistId): ?object
    {
        $items = self::getAllItemsByPlaylistId($playlistId);

        return $items->last();
    }

    /**
     * Get video at specific index from playlist
     */
    public static function getVideoAtIndex(string $playlistId, int $index): ?object
    {
        $items = self::getAllItemsByPlaylistId($playlistId);

        return $items->get($index);
    }

    /**
     * Search for the latest video in a channel with specific keyword
     */
    public static function searchLatestInChannel(string $channelId, string $keyword, int $limit = 1): Collection
    {
        $results = Youtube::searchChannelVideos($keyword, $channelId, $limit, 'date');

        return collect($results);
    }

    /**
     * Get video information by video ID
     */
    public static function getVideoInfo(string $videoId): ?object
    {
        return Youtube::getVideoInfo($videoId);
    }

    /**
     * Build audio+video response array for a channel video
     */
    public static function buildChannelResponse(string $vid, string $title, string $channelName): array
    {
        $image = "https://i.ytimg.com/vi/{$vid}/maxresdefault.jpg";
        $mp4 = config('x-resources.r2_share_video')."/@{$channelName}/{$vid}.mp4";
        $m4a = config('x-resources.r2_share_audio')."/@{$channelName}/{$vid}.m4a";

        $addition = [
            'type' => 'link',
            'data' => [
                'url' => $mp4,
                'title' => $title,
                'description' => "@{$channelName}",
                'image' => $image,
            ],
            'statistics' => [
                'metric' => 'youtube',
                'keyword' => $vid,
                'type' => 'video',
            ],
        ];

        return [
            'type' => 'music',
            'data' => [
                'url' => $m4a,
                'title' => $title,
                'description' => "@{$channelName}",
                'image' => $image,
            ],
            'statistics' => [
                'metric' => 'youtube',
                'keyword' => $vid,
                'type' => 'audio',
            ],
            'addition' => $addition,
        ];
    }

    /**
     * Scrape latest videos/streams from a YouTube channel page
     *
     * @return array<int, array{videoId: string, title: string}>
     */
    public static function scrapeChannel(string $channelName, string $listType = 'videos', int $limit = 0): array
    {
        $url = "https://www.youtube.com/@{$channelName}/{$listType}";
        $response = Http::withHeaders([
            'Accept-Language' => 'zh-CN,zh;q=0.9,en;q=0.8',
        ])->get($url);

        // YouTube channel pages currently ship two co-existing card formats:
        //   - gridVideoRenderer  → "title":{"runs":[{"text":"<title>"}]}
        //   - lockupViewModel    → "title":{"content":"<title>"}
        // The subscribe-prompt panel uses "title":{"simpleText":"..."} and the
        // duration overlay uses "thumbnailOverlayTimeStatusRenderer":{"text":...},
        // both of which we intentionally do not match.
        $re = '/vi\/([^\/]+).*?"title":\{(?:"content":|"runs":\[\{"text":)"(.*?)"/';
        preg_match_all($re, $response->body(), $matches);

        $results = [];
        $count = $limit > 0 ? min($limit, count($matches[1])) : count($matches[1]);
        for ($i = 0; $i < $count; $i++) {
            $results[] = [
                'videoId' => $matches[1][$i],
                'title' => $matches[2][$i],
            ];
        }

        return $results;
    }
}
