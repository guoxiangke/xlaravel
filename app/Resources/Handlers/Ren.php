<?php

namespace App\Resources\Handlers;

use App\Resources\Helpers\YouTubeHelper;
use App\Resources\ResourceResponse;
use Illuminate\Support\Str;

class Ren
{
    public function resolve(string $keyword): ?ResourceResponse
    {
        $offset = substr($keyword, 3) ?: 0;
        $baseKeyword = substr($keyword, 0, 3);

        try {
            if ($baseKeyword >= '813' && $baseKeyword <= '829') {
                return $this->getDailyProgram($baseKeyword, $offset);
            } elseif ($baseKeyword >= '830' && $baseKeyword <= '839') {
                return $this->getSeriesProgram($baseKeyword, $offset);
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error($e->getMessage());
        }

        return null;
    }

    private function getDailyProgram(string $keyword, string $offset): ?ResourceResponse
    {
        $playlistTitles = [
            '813' => ['shorts' => true, 'title' => '每日經文', 'alias' => 'mrjw', 'id' => 'PL942JJGZpDIehJuBSaLIe_-irOegGih9M'],
            '815' => ['shorts' => true, 'title' => '每日詩歌', 'alias' => 'mrsg', 'id' => 'PL942JJGZpDIeF0f51w_ZxoYOAmfXOlEiR'],
            '816' => ['shorts' => true, 'title' => '每日禱告', 'alias' => 'mrdg', 'id' => 'PL942JJGZpDIfIollTxvXVxnR-b8HG0k6n'],
            '817' => ['title' => '喻道故事', 'id' => 'PL942JJGZpDIeu1QnggkoXQ8lymaMbA3gT'],
            '818' => ['shorts' => true, 'title' => '美國政治小知識', 'id' => 'PL942JJGZpDIdSluGl2wRHwoiZ43ucHYXw'],
            '819' => ['shorts' => false, 'title' => '節日特別節目', 'id' => 'PL942JJGZpDIeHgKvbi21itY15k3MTyeWL'],
            '820' => ['shorts' => false, 'title' => '聖經故事', 'id' => 'PL942JJGZpDIfNcwnO1aKqdU8VoXQqBZoN'],
            '821' => ['shorts' => false, 'title' => '我們看世界談話節目', 'id' => 'PL942JJGZpDIddB74WZ7X3CZQUJSpUealR', 'order' => 'asc'],
            '822' => ['shorts' => false, 'title' => '傳奇基督徒的二三事', 'id' => 'PL942JJGZpDIdm1vCiIjrj6pF5ZcwVVcw-'],
            '823' => ['shorts' => false, 'title' => '活力見證', 'id' => 'PL942JJGZpDIcGHU4XTiO3cLoUibfC05gf'],
            '824' => ['shorts' => false, 'title' => '孫老師夜話', 'id' => 'PLKpVlslvGNdjxJOrbOBwibe-MH7XeZfG2'],
        ];

        if (! isset($playlistTitles[$keyword])) {
            return null;
        }

        $config = $playlistTitles[$keyword];

        // Temporarily return mock data due to YouTube API key requirement
        if (! config('services.youtube.api_key')) {
            return ResourceResponse::text([
                'content' => "【{$keyword}】{$config['title']} - 需要配置 YouTube API Key",
            ]);
        }

        $all = YouTubeHelper::getAllItemsByPlaylistId($config['id']);

        if ($all->isEmpty()) {
            return null;
        }

        $item = $config['order'] ?? null ? $all->last() : $all->first();
        $title = $item->snippet->title;
        $vid = $item->snippet->resourceId->videoId;
        $who = 'LFC';

        $url = config('x-resources.r2_share_audio')."/@{$who}/{$vid}.mp4";
        $image = "https://i.ytimg.com/vi/{$vid}/sddefault.jpg";

        if ($keyword == '813') {
            $parts = explode('|', $title);
            $title = trim(implode('|', array_slice($parts, 0, -1)));
        }

        if (Str::startsWith($config['title'], '每日')) {
            $description = '@LFC活力生命 每日更新';
        } else {
            $title = "【{$keyword}】{$config['title']} {$title}";
            $description = $item->snippet->description;
        }

        $addition = null;
        if (isset($config['alias'])) {
            $alias = $config['alias'];
            $fileName = $alias.date('ymd').'.mp4';
            $url = config('x-resources.r2_share_audio')."/Ren/{$alias}/{$fileName}";

            $addition = ResourceResponse::text([
                'content' => "【{$keyword}】{$title}",
            ]);

            $title = "【{$keyword}】";
            $description = '@LFC活力生命';
        }

        $videoResponse = ResourceResponse::link([
            'url' => $url,
            'title' => $title,
            'description' => $description,
            'image' => $image,
            'vid' => $vid,
        ], [], $addition);

        if (! ($config['shorts'] ?? false)) {
            // Add audio version for non-shorts
            $audioResponse = ResourceResponse::music([
                'url' => str_replace('.mp4', '.m4a', $url),
                'title' => $title,
                'description' => $description,
                'image' => $image,
                'vid' => $vid,
            ]);

            $videoResponse->addition = $audioResponse;
        }

        return $videoResponse;
    }

    private function getSeriesProgram(string $keyword, string $offset): ?ResourceResponse
    {
        $playlistTitles = [
            '830' => ['title' => '约翰福音的密码读书会', 'id' => 'PL942JJGZpDIeQKLByNTHavZ-M2gMtMN8s'],
            '831' => ['title' => '禱告睡眠音樂', 'id' => 'PL942JJGZpDIc2If_FgYxragz02cJHwm-y'],
            '832' => ['title' => '神學三十分', 'who' => '拜歐拉中心活力論壇', 'id' => 'PLZvcyxLkKfh-ocyx76-EvD-TD8CsCdhb_'],
        ];

        if (! isset($playlistTitles[$keyword])) {
            return null;
        }

        $config = $playlistTitles[$keyword];
        $all = YouTubeHelper::getAllItemsByPlaylistId($config['id']);

        if ($all->isEmpty()) {
            return null;
        }

        $total = $all->count();
        $index = date('z') % $total;

        if ($keyword == '832') {
            $all = $all->reverse()->values();
            $index = $total - 1;
        }

        if ($offset) {
            $index = (int) $offset % $total;
            if ($index == 0) {
                $index = $total;
            }
            $index--;
        }

        $item = $all[$index];
        $vid = $item->snippet->resourceId->videoId;
        $title = $item->snippet->title;
        $description = $item->snippet->description;
        $who = $config['who'] ?? 'LFC';

        $url = config('x-resources.r2_share_audio')."/@{$who}/{$vid}.mp4";
        $image = "https://i.ytimg.com/vi/{$vid}/sddefault.jpg";

        $videoResponse = ResourceResponse::link([
            'url' => $url,
            'title' => "【{$keyword}】{$config['title']} {$title}",
            'description' => $description,
            'image' => $image,
            'vid' => $vid,
        ]);

        // Add audio version
        $audioResponse = ResourceResponse::music([
            'url' => str_replace('.mp4', '.m4a', $url),
            'title' => "【{$keyword}】{$config['title']} {$title}",
            'description' => $description,
            'image' => $image,
            'vid' => $vid,
        ]);

        if ($keyword == '832') {
            return $audioResponse; // Return audio only for 832
        }

        $videoResponse->addition = $audioResponse;

        return $videoResponse;
    }
}
