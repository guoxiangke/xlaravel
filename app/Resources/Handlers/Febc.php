<?php

namespace App\Resources\Handlers;

use App\Resources\ResourceResponse;
use Illuminate\Support\Facades\Http;

class Febc
{
    private const PROGRAMS = [
        '701' => ['title' => '灵程真言', 'code' => 'tllczy'],
        '702' => ['title' => '喜乐灵程', 'code' => 'tljd'],
        '703' => ['title' => '认识你真好', 'code' => 'vof'],
        '704' => ['title' => '真爱驻我家', 'code' => 'tltl'],
        '705' => ['title' => '尔道自建', 'code' => 'edzj'],
        '706' => ['title' => '旷野吗哪', 'code' => 'mw'],
        '707' => ['title' => '真道分解', 'code' => 'be'],
        '708' => ['title' => '馒头的对话(周1-5)', 'code' => 'mn'],
        '709' => ['title' => '豪放乐龄', 'code' => 'hfln'],
        '710' => ['title' => '天路男行客', 'code' => 'pm'],
        '711' => ['title' => '肋骨咏叹调', 'code' => 'sz'],
        '712' => ['title' => '颜明放羊班', 'code' => 'ym'],
        '713' => ['title' => '真爱世界', 'code' => 'tv'],
        '714' => ['title' => '经今有味', 'code' => 'jjyw'],
    ];

    public function getResourceList(): array
    {
        $list = [];
        foreach (self::PROGRAMS as $key => $program) {
            $list[] = [
                'keyword' => (string) $key,
                'title' => $program['title'],
            ];
        }

        return $list;
    }

    public function resolve(string $keyword): ?ResourceResponse
    {
        if ($keyword === '700') {
            return $this->getProgramList();
        }

        if (array_key_exists($keyword, self::PROGRAMS)) {
            return $this->getAudioProgram($keyword);
        }

        return null;
    }

    private function getProgramList(): ResourceResponse
    {
        $content = collect(self::PROGRAMS)
            ->map(fn ($program, $key) => "【{$key}】{$program['title']}")
            ->implode("\n");

        return ResourceResponse::text([
            'content' => $content,
        ]);
    }

    private function getAudioProgram(string $keyword): ?ResourceResponse
    {
        $program = self::PROGRAMS[$keyword];

        try {
            // Get FEBC program data
            $jsonDomain = config('x-resources.resource_all_json_domain');
            $response = Http::get($jsonDomain."single_{$program['code']}_songs.json");

            if (! $response->successful()) {
                return null;
            }

            $json = $response->json();

            if (empty($json)) {
                return null;
            }

            $jdata = end($json);
            $dateStr = $jdata['time'] ?? date('ymd');
            $title = "【{$keyword}】{$program['title']}-".$dateStr;

            return ResourceResponse::music([
                'url' => $jdata['path'] ?? '',
                'title' => $title,
                'description' => $jdata['title'] ?? $program['title'],
                'image' => $jdata['image'] ?? '',
            ]);

        } catch (\Exception $e) {
            return null;
        }
    }
}
