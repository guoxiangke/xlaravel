<?php

namespace App\Resources\Handlers;

use App\Resources\ResourceResponse;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class PastorFei
{
    private const KEYWORD = '805';

    private const CACHE_KEY = 'xbot.keyword.pastorfei.titles';

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

        $now = now()->setTimezone('Asia/Shanghai');

        if ($now->isSunday()) {
            return null;
        }

        $date = $now->format('ymd');
        $url = config('x-resources.r2_share_audio').'/boteli/'.$date.'.MP3';

        return ResourceResponse::music([
            'url' => $url,
            'title' => $this->resolveTitle($date, $now),
            'description' => $date,
        ], [
            'metric' => 'PastorFei',
            'keyword' => self::KEYWORD,
            'type' => 'audio',
        ]);
    }

    private function resolveTitle(string $date, CarbonInterface $now): string
    {
        $titles = Cache::remember(self::CACHE_KEY, $now->copy()->endOfDay(), function (): array {
            $url = config('x-resources.r2_share_audio').'/boteli/xf.csv';
            $response = Http::get($url);

            if ($response->failed()) {
                return [];
            }

            return $this->parseCsv($response->body());
        });

        if (! isset($titles[$date]) || $titles[$date] === '') {
            return '伯特利每日灵修';
        }

        return $titles[$date];
    }

    /**
     * @return array<string, string>
     */
    private function parseCsv(string $body): array
    {
        $lines = preg_split('/\r\n|\r|\n/', trim($body)) ?: [];
        array_shift($lines);

        $titles = [];
        foreach ($lines as $line) {
            if ($line === '') {
                continue;
            }
            $parts = str_getcsv($line);
            if (count($parts) >= 2) {
                $titles[trim($parts[0])] = trim($parts[1]);
            }
        }

        return $titles;
    }
}
