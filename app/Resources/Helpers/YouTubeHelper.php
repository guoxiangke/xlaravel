<?php

namespace App\Resources\Helpers;

use Illuminate\Support\Collection;
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
}
