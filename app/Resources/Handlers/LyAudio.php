<?php

namespace App\Resources\Handlers;

use App\Resources\ResourceResponse;
use Illuminate\Support\Facades\Http;

class LyAudio
{

    //   完全空闲的编码：
    //      - 623, 627, 631-636, 638, 647, 655, 656, 658, 661-663, 665-667, 669, 670, 673, 677, 679, 682-697, 699
    private const PROGRAMS = [
        '生活智慧' => [
            '612' => ['title' => '书香园地', 'code' => 'bc'],
            '610' => ['title' => '天路男行客', 'code' => 'pm'],
            '619' => ['title' => '星之导航', 'code' => 'rn'],
            '678' => ['title' => '肋骨咏叹调', 'code' => 'sz'],
            '604' => ['title' => '真爱世界', 'code' => 'tv'],
            '675' => ['title' => '不孤单地球', 'code' => 'wc'],
            '674' => ['title' => '深度泛桌派', 'code' => 'pt'],
            '668' => ['title' => '岁月正好', 'code' => 'ec'],
            '613' => ['title' => 'i-Radio爱广播', 'code' => 'ir'],
            '614' => ['title' => '今夜心未眠', 'code' => 'rt'],
            '657' => ['title' => '天使夜未眠', 'code' => 'ka'],
            '611' => ['title' => '零点凡星', 'code' => 'sa'],
        ],

        '少儿家庭' => [
            '605' => ['title' => '一起成长吧！', 'code' => 'gg'],
            '659' => ['title' => '爆米花', 'code' => 'pc'],
            '602' => ['title' => '欢乐下课趣', 'code' => 'fa'],
            '607' => ['title' => '将将！百宝书开箱', 'code' => 'bx'],
            '664' => ['title' => '小羊圣经故事', 'code' => 'cs'],
            '606' => ['title' => '亲情不断电', 'code' => 'up'],
            '660' => ['title' => '我们的时间', 'code' => 'ut'],
        ],

        '诗歌音乐' => [
            '680' => ['title' => '午的空间', 'code' => 'gf'],
            '608' => ['title' => '一起弹唱吧！', 'code' => 'pp'],
            '616' => ['title' => '与你有乐', 'code' => 'jr'],
        ],

        '生命成长' => [
            '601' => ['title' => '无限飞行号', 'code' => 'iba'],
            '603' => ['title' => '空中辅导', 'code' => 'cc'],
            '620' => ['title' => '旷野吗哪', 'code' => 'mw'],
            '617' => ['title' => '牧者抱抱团', 'code' => 'pk'],
            '618' => ['title' => '献上今天', 'code' => 'dy'],
            '698' => ['title' => '馒头的对话', 'code' => 'mn'],
            '639' => ['title' => '青春良伴', 'code' => 'yb'],
            '646' => ['title' => '晨曦讲座', 'code' => 'ds'],
            '624' => ['title' => '成主学堂', 'code' => 'dr'],
            '630' => ['title' => '主啊！995！', 'code' => 'mg'],
            '640' => ['title' => '这一刻，清心', 'code' => 'mpa'],
            '628' => ['title' => '空中崇拜', 'code' => 'aw'],
            '652' => ['title' => '教会年历‧家庭崇拜', 'code' => 'gw'],
            '672' => ['title' => '燃亮的一生', 'code' => 'ls'],
            '609' => ['title' => '颜明放羊班', 'code' => 'ym'],
            '626' => ['title' => '微声盼望', 'code' => 'wr'],
        ],

        '圣经讲解' => [
            '621' => ['title' => '真道分解', 'code' => 'be'],
            '622' => ['title' => '圣言盛宴', 'code' => 'bs'],
            '676' => ['title' => '穿越圣经', 'code' => 'ttb'],
            '654' => ['title' => '与神同行', 'code' => 'it'],
            '681' => ['title' => '卢文心底话', 'code' => 'fh'],
            '629' => ['title' => '善牧良言', 'code' => 'yp'],
            '615' => ['title' => '泛桌茶经班', 'code' => 'fd'],
            '625' => ['title' => '真理之光', 'code' => 'th'],
            '648' => ['title' => '天路导向', 'code' => 'wa'],
        ],

        '课程训练' => [
            '641' => ['title' => '良友圣经学院（启航课程）', 'code' => 'ltsnp'],
            '642' => ['title' => '良院普及本科第一套', 'code' => 'ltstpa1'],
            '643' => ['title' => '良院普及本科第二套', 'code' => 'ltstpa2'],
            '644' => ['title' => '良院普及进深第一套', 'code' => 'ltstpb1'],
            '645' => ['title' => '良院普及进深第二套', 'code' => 'ltstpb2'],
            '671' => ['title' => '良院讲台', 'code' => 'vp'],
        ],

        '其他语言' => [
            '650' => ['title' => '恩典与真理', 'code' => 'gt'],
            '651' => ['title' => '爱在人间（云南话）', 'code' => 'ynf'],
            '649' => ['title' => '天路导向（粤、英）', 'code' => 'cwa'],
            '637' => ['title' => '旷野吗哪（客家语）', 'code' => 'hmw'],
            '653' => ['title' => '旷野吗哪（粤语）', 'code' => 'cmw'],
        ],
    ];

    public function getResourceList(): array
    {
        $list = [];
        foreach (self::PROGRAMS as $category => $programs) {
            foreach ($programs as $key => $program) {
                $list[] = [
                    'keyword' => (string) $key,
                    'title' => $program['title'],
                ];
            }
        }

        return $list;
    }

    public function resolve(string $keyword): ?ResourceResponse
    {
        // 支持600-699的3位数关键字
        if ($keyword === '600') {
            return $this->getProgramList();
        }

        $numKeyword = (int) substr($keyword, 0, 3);
        $program = $this->findProgram($numKeyword);
        $code = $program['code'] ?? null;

        if (empty($code)) {
            return null;
        }

        return $this->getAudioProgram($numKeyword, $code);
    }

    private function findProgram(int $keyword): ?array
    {
        foreach (self::PROGRAMS as $category => $programs) {
            if (isset($programs[(string) $keyword])) {
                return $programs[(string) $keyword];
            }
        }

        return null;
    }

    private function getProgramList(): ResourceResponse
    {
        $content = '';

        foreach (self::PROGRAMS as $category => $programs) {
            $content .= "====={$category}=====\n";
            foreach ($programs as $key => $program) {
                $content .= "【{$key}】{$program['title']}\n";
            }
        }

        return ResourceResponse::text([
            'content' => trim($content),
        ]);
    }

    private function getAudioProgram(int $keyword, string $code): ?ResourceResponse
    {
        try {
            // 从良友电台API获取数据
            $response = Http::get("https://x.lydt.work/api/program/{$code}");
            $json = $response->json();

            if (empty($json['data'])) {
                return null;
            }

            $item = $json['data'][0];
            $url = $item['link'];

            // 替换CDN域名
            $url = str_replace(
                'https://x.lydt.work/storage/',
                'https://d3ml8yyp1h3hy5.cloudfront.net/',
                $url
            );

            // 特殊处理某些节目的URL
            if (in_array($keyword, [641, 642, 643, 644, 645])) {
                $url = str_replace('/ly/audio/', '/lts/', $url);
            }

            $image = "https://txly2.net/images/program_banners/{$code}_prog_banner_sq.png";
            $dateStr = str_replace($code, '', $item['alias']);

            return ResourceResponse::music([
                'url' => $url,
                'title' => "【{$keyword}】".str_replace('圣经', 'SJ', $item['program']['name']).' '.$dateStr,
                'description' => str_replace('教会', 'JH', $item['description']),
                'image' => $image,
            ]);
        } catch (\Exception $e) {
            return null;
        }
    }
}
