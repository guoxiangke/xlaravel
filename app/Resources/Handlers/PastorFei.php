<?php

namespace App\Resources\Handlers;

use App\Resources\ResourceResponse;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use ZipArchive;

class PastorFei
{
    private const KEYWORD = '805';

    private const CACHE_KEY_PREFIX = 'xbot.keyword.pastorfei.titles.';

    private const SHEET_NAME = 'in';

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
        $titles = Cache::remember(self::CACHE_KEY_PREFIX.$date, $now->copy()->endOfDay(), function () use ($date): array {
            $url = config('x-resources.r2_share_audio').'/boteli/xf2.xlsx';
            $response = Http::get($url, ['v' => $date]);

            if ($response->failed()) {
                return [];
            }

            return $this->parseXlsx($response->body());
        });

        if (! isset($titles[$date]) || $titles[$date] === '') {
            return '伯特利每日灵修';
        }

        return $titles[$date];
    }

    /**
     * @return array<string, string>
     */
    private function parseXlsx(string $body): array
    {
        $tmp = tempnam(sys_get_temp_dir(), 'xf2_');
        if ($tmp === false) {
            return [];
        }

        try {
            file_put_contents($tmp, $body);

            $zip = new ZipArchive;
            if ($zip->open($tmp) !== true) {
                return [];
            }

            try {
                $sheetPath = $this->resolveSheetPath($zip, self::SHEET_NAME);
                if ($sheetPath === null) {
                    return [];
                }

                $strings = $this->readSharedStrings($zip);
                $sheetXml = $zip->getFromName($sheetPath);
                if ($sheetXml === false) {
                    return [];
                }

                return $this->extractTitles($sheetXml, $strings);
            } finally {
                $zip->close();
            }
        } catch (RuntimeException) {
            return [];
        } finally {
            @unlink($tmp);
        }
    }

    private function resolveSheetPath(ZipArchive $zip, string $sheetName): ?string
    {
        $workbook = $zip->getFromName('xl/workbook.xml');
        $rels = $zip->getFromName('xl/_rels/workbook.xml.rels');
        if ($workbook === false || $rels === false) {
            return null;
        }

        $wb = @simplexml_load_string($workbook);
        $rl = @simplexml_load_string($rels);
        if ($wb === false || $rl === false) {
            return null;
        }

        $relId = null;
        foreach ($wb->sheets->sheet ?? [] as $sheet) {
            $attrs = $sheet->attributes();
            $rAttrs = $sheet->attributes('r', true);
            if ((string) ($attrs['name'] ?? '') === $sheetName) {
                $relId = (string) ($rAttrs['id'] ?? '');
                break;
            }
        }

        if ($relId === null || $relId === '') {
            return null;
        }

        foreach ($rl->Relationship ?? [] as $rel) {
            $a = $rel->attributes();
            if ((string) ($a['Id'] ?? '') === $relId) {
                $target = (string) ($a['Target'] ?? '');

                return str_starts_with($target, '/') ? ltrim($target, '/') : 'xl/'.$target;
            }
        }

        return null;
    }

    /**
     * @return array<int, string>
     */
    private function readSharedStrings(ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');
        if ($xml === false) {
            return [];
        }

        $sst = @simplexml_load_string($xml);
        if ($sst === false) {
            return [];
        }

        $strings = [];
        foreach ($sst->si ?? [] as $si) {
            if (isset($si->t)) {
                $strings[] = (string) $si->t;

                continue;
            }

            $buf = '';
            foreach ($si->r ?? [] as $run) {
                $buf .= (string) ($run->t ?? '');
            }
            $strings[] = $buf;
        }

        return $strings;
    }

    /**
     * @param  array<int, string>  $strings
     * @return array<string, string>
     */
    private function extractTitles(string $sheetXml, array $strings): array
    {
        $sheet = @simplexml_load_string($sheetXml);
        if ($sheet === false) {
            return [];
        }

        $titles = [];
        $first = true;
        foreach ($sheet->sheetData->row ?? [] as $row) {
            if ($first) {
                $first = false;

                continue;
            }

            $cells = [];
            foreach ($row->c ?? [] as $cell) {
                $ref = (string) ($cell->attributes()['r'] ?? '');
                $col = preg_replace('/\d+$/', '', $ref);
                $type = (string) ($cell->attributes()['t'] ?? '');
                $raw = isset($cell->v) ? (string) $cell->v : '';
                $value = $type === 's' ? ($strings[(int) $raw] ?? '') : $raw;
                $cells[$col] = $value;
            }

            $date = trim($cells['A'] ?? '');
            $title = trim($cells['B'] ?? '');
            if ($date !== '') {
                $titles[$date] = $title;
            }
        }

        return $titles;
    }
}
