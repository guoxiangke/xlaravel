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

    public function resolve(string $keyword): ?ResourceResponse
    {
        // 支持600-699的3位数关键字
        if ($keyword === '600') {
            return $this->getProgramList();
        }

        $numKeyword = (int) substr($keyword, 0, 3);

        if (! ($numKeyword >= 601 && $numKeyword <= 699 && isset($this->keywordMap[$numKeyword]))) {
            return null;
        }

        $code = $this->keywordMap[$numKeyword] ?? '';

        if (empty($code)) {
            return null;
        }

        return $this->getAudioProgram($numKeyword, $code);
    }

    private function getProgramList(): ResourceResponse
    {
        $content = <<<'EOD'
=====生活智慧=====
【612】书香园地
【610】天路男行客
【619】星之导航
【678】肋骨咏叹调
【604】真爱世界
【675】不孤单地球
【674】深度泛桌派
【668】岁月正好
【613】i-Radio爱广播
【614】今夜心未眠
【657】天使夜未眠
【611】零点凡星
=====少儿家庭=====
【605】一起成长吧！
【659】爆米花
【602】欢乐下课趣
【607】将将！百宝书开箱
【664】小羊圣经故事
【606】亲情不断电
【660】我们的时间
=====诗歌音乐=====
【680】午的空间
【608】一起弹唱吧！
【616】与你有乐
=====生命成长=====
【601】无限飞行号
【603】空中辅导
【620】旷野吗哪
【617】牧者抱抱团
【618】献上今天
【698】馒头的对话
EOD;

        return ResourceResponse::text([
            'content' => $content,
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
