<?php

namespace App\Resources\Handlers;

use App\Resources\ResourceResponse;
use Illuminate\Support\Facades\Http;

class LyAudio
{
    // 映射关系：关键字 => 程序代码
    private array $keywordMap = [
        601 => 'iba', 602 => 'fa', 603 => 'cc', 604 => 'tv', 605 => 'gg',
        606 => 'up', 607 => 'bx', 608 => 'pp', 609 => 'ym', 610 => 'pm',
        611 => 'sa', 612 => 'bc', 613 => 'ir', 614 => 'rt', 615 => 'fd',
        616 => 'jr', 617 => 'pk', 618 => 'dy', 619 => 'rn', 620 => 'mw',
        621 => 'be', 622 => 'bs', 623 => '', 624 => 'dr', 625 => 'th',
        626 => 'wr', 627 => '', 628 => 'aw', 629 => 'yp', 630 => 'mg',
        631 => 'cfbwh', 632 => 'cedna', 633 => 'cfcbp', 634 => 'cfbls',
        35 => 'cfbsg', 636 => 'cgaal', 637 => 'hmw', 638 => '', 639 => 'yb',
        640 => 'mpa', 641 => 'ltsnp', 642 => 'ltstpa1', 643 => 'ltstpa2',
        644 => 'ltstpb1', 645 => 'ltstpb2', 646 => 'ds', 647 => '',
        648 => 'wa', 649 => 'cwa', 650 => 'gt', 651 => 'ynf', 652 => 'gw',
        653 => 'cmw', 654 => 'it', 655 => '', 656 => '', 657 => 'ka',
        658 => '', 659 => 'pc', 660 => 'ut', 661 => '', 662 => '', 663 => '',
        664 => 'cs', 665 => '', 666 => '', 667 => '', 668 => 'ec', 669 => '',
        670 => '', 671 => 'vp', 672 => 'ls', 673 => '', 674 => 'pt',
        675 => 'wc', 676 => 'ttb', 677 => '', 678 => 'sz', 679 => '',
        680 => 'gf', 681 => 'fh', 682 => 'caabg', 683 => 'caawm',
        684 => 'caccp', 685 => 'caatp', 686 => 'caaco', 687 => 'cacac',
        698 => 'mn',
    ];

    private const PROGRAMS = [
        // 生活智慧
        '612' => ['title' => '书香园地', 'code' => 'bc', 'category' => '生活智慧'],
        '610' => ['title' => '天路男行客', 'code' => 'pm', 'category' => '生活智慧'],
        '619' => ['title' => '星之导航', 'code' => 'rn', 'category' => '生活智慧'],
        '678' => ['title' => '肋骨咏叹调', 'code' => 'sz', 'category' => '生活智慧'],
        '604' => ['title' => '真爱世界', 'code' => 'tv', 'category' => '生活智慧'],
        '675' => ['title' => '不孤单地球', 'code' => 'wc', 'category' => '生活智慧'],
        '674' => ['title' => '深度泛桌派', 'code' => 'pt', 'category' => '生活智慧'],
        '668' => ['title' => '岁月正好', 'code' => 'ec', 'category' => '生活智慧'],
        '613' => ['title' => 'i-Radio爱广播', 'code' => 'ir', 'category' => '生活智慧'],
        '614' => ['title' => '今夜心未眠', 'code' => 'rt', 'category' => '生活智慧'],
        '657' => ['title' => '天使夜未眠', 'code' => 'ka', 'category' => '生活智慧'],
        '611' => ['title' => '零点凡星', 'code' => 'sa', 'category' => '生活智慧'],

        // 少儿家庭
        '605' => ['title' => '一起成长吧！', 'code' => 'gg', 'category' => '少儿家庭'],
        '659' => ['title' => '爆米花', 'code' => 'pc', 'category' => '少儿家庭'],
        '602' => ['title' => '欢乐下课趣', 'code' => 'fa', 'category' => '少儿家庭'],
        '607' => ['title' => '将将！百宝书开箱', 'code' => 'bx', 'category' => '少儿家庭'],
        '664' => ['title' => '小羊圣经故事', 'code' => 'cs', 'category' => '少儿家庭'],
        '606' => ['title' => '亲情不断电', 'code' => 'up', 'category' => '少儿家庭'],
        '660' => ['title' => '我们的时间', 'code' => 'ut', 'category' => '少儿家庭'],

        // 诗歌音乐
        '680' => ['title' => '午的空间', 'code' => 'gf', 'category' => '诗歌音乐'],
        '608' => ['title' => '一起弹唱吧！', 'code' => 'pp', 'category' => '诗歌音乐'],
        '616' => ['title' => '与你有乐', 'code' => 'jr', 'category' => '诗歌音乐'],

        // 生命成长
        '601' => ['title' => '无限飞行号', 'code' => 'iba', 'category' => '生命成长'],
        '603' => ['title' => '空中辅导', 'code' => 'cc', 'category' => '生命成长'],
        '620' => ['title' => '旷野吗哪', 'code' => 'mw', 'category' => '生命成长'],
        '617' => ['title' => '牧者抱抱团', 'code' => 'pk', 'category' => '生命成长'],
        '618' => ['title' => '献上今天', 'code' => 'dy', 'category' => '生命成长'],
        '698' => ['title' => '馒头的对话', 'code' => 'mn', 'category' => '生命成长'],
        '639' => ['title' => '青春良伴', 'code' => 'yb', 'category' => '生命成长'],
        '646' => ['title' => '晨曦讲座', 'code' => 'ds', 'category' => '生命成长'],
        '624' => ['title' => '成主学堂', 'code' => 'dr', 'category' => '生命成长'],
        '630' => ['title' => '主啊！995！', 'code' => 'mg', 'category' => '生命成长'],
        '640' => ['title' => '这一刻，清心', 'code' => 'mpa', 'category' => '生命成长'],
        '628' => ['title' => '空中崇拜', 'code' => 'aw', 'category' => '生命成长'],
        '652' => ['title' => '教会年历‧家庭崇拜', 'code' => 'gw', 'category' => '生命成长'],
        '672' => ['title' => '燃亮的一生', 'code' => 'ls', 'category' => '生命成长'],
        '609' => ['title' => '颜明放羊班', 'code' => 'ym', 'category' => '生命成长'],
        '626' => ['title' => '微声盼望', 'code' => 'wr', 'category' => '生命成长'],

        // 圣经讲解
        '621' => ['title' => '真道分解', 'code' => 'be', 'category' => '圣经讲解'],
        '622' => ['title' => '圣言盛宴', 'code' => 'bs', 'category' => '圣经讲解'],
        '676' => ['title' => '穿越圣经', 'code' => 'ttb', 'category' => '圣经讲解'],
        '654' => ['title' => '与神同行', 'code' => 'it', 'category' => '圣经讲解'],
        '681' => ['title' => '卢文心底话', 'code' => 'fh', 'category' => '圣经讲解'],
        '629' => ['title' => '善牧良言', 'code' => 'yp', 'category' => '圣经讲解'],
        '615' => ['title' => '泛桌茶经班', 'code' => 'fd', 'category' => '圣经讲解'],
        '625' => ['title' => '真理之光', 'code' => 'th', 'category' => '圣经讲解'],
        '648' => ['title' => '天路导向', 'code' => 'wa', 'category' => '圣经讲解'],

        // 课程训练
        '641' => ['title' => '良友圣经学院（启航课程）', 'code' => 'ltsnp', 'category' => '课程训练'],
        '642' => ['title' => '良院普及本科第一套', 'code' => 'ltstpa1', 'category' => '课程训练'],
        '643' => ['title' => '良院普及本科第二套', 'code' => 'ltstpa2', 'category' => '课程训练'],
        '644' => ['title' => '良院普及进深第一套', 'code' => 'ltstpb1', 'category' => '课程训练'],
        '645' => ['title' => '良院普及进深第二套', 'code' => 'ltstpb2', 'category' => '课程训练'],
        '671' => ['title' => '良院讲台', 'code' => 'vp', 'category' => '课程训练'],

        // 其他语言
        '650' => ['title' => '恩典与真理', 'code' => 'gt', 'category' => '其他语言'],
        '651' => ['title' => '爱在人间（云南话）', 'code' => 'ynf', 'category' => '其他语言'],
        '649' => ['title' => '天路导向（粤、英）', 'code' => 'cwa', 'category' => '其他语言'],
        '637' => ['title' => '旷野吗哪（客家语）', 'code' => 'hmw', 'category' => '其他语言'],
        '653' => ['title' => '旷野吗哪（粤语）', 'code' => 'cmw', 'category' => '其他语言'],
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
        // 支持600-699的3位数关键字
        if ($keyword === '600') {
            return $this->getProgramList();
        }

        $numKeyword = (int) substr($keyword, 0, 3);
        $code = self::PROGRAMS[$numKeyword]['code'] ?? $this->keywordMap[$numKeyword] ?? null;

        if (empty($code)) {
            return null;
        }

        return $this->getAudioProgram($numKeyword, $code);
    }

    private function getProgramList(): ResourceResponse
    {
        $grouped = collect(self::PROGRAMS)->groupBy('category');
        $content = '';

        foreach ($grouped as $category => $items) {
            $content .= "====={$category}=====\n";
            foreach ($items as $key => $item) {
                $content .= "【{$key}】{$item['title']}\n";
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
