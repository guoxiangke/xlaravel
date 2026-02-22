<?php

namespace App\Resources\Handlers;

use App\Resources\ResourceResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class Hland
{
    public function getResourceList(): array
    {
        return [
            ['keyword' => 'hl46436', 'title' => '《灵魂幸存者》杨腓力（合集）'],
            ['keyword' => '78201', 'title' => '历史的轨迹二千年史(祁伯尔)'],
            ['keyword' => '78202', 'title' => '贤德的妻子(玛莎·佩斯)'],
            ['keyword' => '78203', 'title' => '中国家庭教会史(王怡)'],
            ['keyword' => '78204', 'title' => '讲道与讲道的人(钟马田)'],
        ];
    }

    public function resolve(string $keyword): ?ResourceResponse
    {
        $albums = [
            [
                'id' => '46436',
                'title' => '《灵魂幸存者》杨腓力（合集）',
                'description' => '我们的生命常常需要从那些伟大人物的身上汲取力量。在《灵魂幸存者》中，著名作家杨腓力用十三篇精彩地人物素描，活画出对他的灵性生活影响至深的十三个人的生命故事：马丁·路德·金，甘地，托尔斯泰，陀思妥耶夫斯基，班德医生，卢云神父……他们并不一定是传统意义上的"信心伟人"，有的甚至也未必是基督徒，但是他们的怀疑和坚定，他们的软弱和勇敢，他们的瑕疵和"刺"，让我们恍悟：原来一个朝圣者的真实挣扎可以成为其他人安慰的源头和信仰的磐石，而基督徒生命的本相更是充实而丰盛的。《灵魂幸存者》不仅仅是杨腓力个人的信仰履历表，也是弟兄姐妹的灵性参考书，帮助我们能更深入地内省我们的信仰，更真实地面对我们的生命。',
                'total' => '33',
                'image' => 'https://wsrv.nl/?url=media.h.land/prod/20220802-081010.828-small.jpg',
            ],
        ];

        if (Str::startsWith($keyword, 'hl')) {
            $content = substr($keyword, 2);

            $albumIds = array_column($albums, 'id');
            usort($albumIds, function ($a, $b) {
                return strlen($b) - strlen($a);
            });

            $matchedId = null;
            $chapterIndex = null;

            foreach ($albumIds as $id) {
                if (Str::startsWith($content, $id)) {
                    $matchedId = $id;

                    $remainder = substr($content, strlen($id));

                    if (! empty($remainder)) {
                        if (preg_match('/^(\d+)$/', $remainder)) {
                            $chapterIndex = (int) $remainder;
                        }
                    }

                    break;
                }
            }

            $album = collect($albums)->firstWhere('id', $matchedId);

            if (! $album) {
                return null;
            }

            $title = $album['title'];
            $albumId = $album['id'];
            $total = $album['total'];

            $index = $chapterIndex ? ($chapterIndex - 1) % $total : date('z') % $total;

            $cacheKey = "xbot.keyword.hland.{$albumId}.{$index}";
            $data = Cache::get($cacheKey);

            if (! $data) {
                try {
                    $url = "https://pub-3813a5d14cba4eaeb297a0dba302143c.r2.dev/hland/{$albumId}.json";
                    $json = Http::get($url)->json();

                    if (empty($json) || ! isset($json[$index])) {
                        return null;
                    }

                    $description = $json[$index]['title'];
                    $blogId = basename(parse_url($json[$index]['url'], PHP_URL_PATH));

                    $audioUrl = config('x-resources.r2_share_audio')."/hland/{$title}/{$blogId}.mp3";

                    $data = [
                        'url' => $audioUrl,
                        'title' => '【'.($index + 1).'/'.$total.'】'.$title,
                        'description' => $description,
                        'image' => $album['image'],
                    ];

                    Cache::put($cacheKey, $data, strtotime('tomorrow') - time());
                } catch (\Exception $e) {
                    return null;
                }
            }

            return ResourceResponse::music($data, [
                'metric' => class_basename(__CLASS__),
                'keyword' => $albumId,
            ]);
        }

        if (Str::startsWith($keyword, '782') && strlen($keyword) >= 3) {
            $albums = [];

            $albums['历史的轨迹二千年史(祁伯尔)'] = [
                '204719' => '01简介及译序',
                '204734' => '第01章 新约教会的诞生',
                '204735' => '第02章 教会在风暴中',
                '204736' => '第03章 教会内部的成长（33－325）',
                '204737' => '第04章 教会全面得胜（313）',
                '204738' => '第05章 教会日形稳固（325－451）',
                '204741' => '第06章 教会渐趋腐化（100－461）',
                '204743' => '第07章 教会历劫而存，继续增长（376－754）（1－6）',
                '204745' => '第07章 教会历劫而存，继续增长（376－754）（7－11）',
                '204746' => '第08章 教会丧失领域（632－732）',
                '204747' => '第09章 教会组成联盟（751－800）',
                '204748' => '第10章 教皇权势的发展（461－1073）',
                '204751' => '第11章 教会被政府控制（885－1049）',
                '204752' => '第12章 教会分裂（1054）',
                '204753' => '第13章 修道主义与克吕尼革新运动',
                '204755' => '第14章 教会为自由奋斗（1049－1058）',
                '204758' => '第15章 教会继续为自由奋斗（1059－1073）',
                '204760' => '第16章 教会被迫妥协（1073－1122）',
                '204762' => '第17章 教会发起十字军运动（1096－1291）',
                '204764' => '第18章 教会权势巅峰时期（1198－1216）',
                '204766' => '第19章 教会权势衰微（1294－1417）',
                '204768' => '第20章 教会内部的困扰（1200－1517）',
                '204769' => '第21章 教会开始动摇（1517年10月31日）',
                '204772' => '第22章 教会大大骚动（1517－1521）',
                '204774' => '第23章 德国教会的改革',
                '204776' => '第24章 瑞士的改教运动',
                '204777' => '第25章 重洗派',
                '204779' => '第26章 西欧的改教运动',
                '204781' => '第27章 苏格兰教会的改革（1557－1570）',
                '204782' => '第28章 英国教会的改革（1534－1563）',
                '204783' => '第29章 罗马教会从事改革（1545－1563）',
                '204784' => '第30章 复原派教会为生存奋斗（1546－1648）',
                '204785' => '第31章 英国教会仍然动荡（1558－1689）',
                '204786' => '第32章 公理派 浸礼派',
                '204789' => '第33章 亚米纽斯主义 贵格派',
                '204791' => '第34章 敬虔主义 莫拉维弟兄会',
                '204793' => '第35章 苏西尼主义 神体一位论 现代主义',
                '204796' => '第36章 循道派',
                '204798' => '第37章 东方教会与罗马教会',
                '204800' => '第38章 德国与英国的宗教生活',
                '204801' => '第39章 改革宗教会大受逼迫',
                '204803' => '第40章 教会再度增长－自公元1500年到现在',
                '204804' => '第41章 教会进入新大陆 1-4节',
                '204806' => '第41章 教会进入新大陆 5-7节',
                '204808' => '第41章 教会进入新大陆 8-10节',
                '204809' => '第41章 教会进入新大陆 11-13节',
                '204810' => '第42章 教会经历大觉醒',
                '204811' => '第43章 新兴国家的教会',
                '204812' => '第44章 十九世纪初期的教会1-6节',
                '204813' => '第44章 十九世纪初期的教会7-10节',
                '204814' => '第45章 教会处于混乱时期1-8节',
                '204816' => '第45章 教会处于混乱时期9-15节',
                '204817' => '第46章 教会面对新问题1-6节',
                '204819' => '第46章 教会面对新问题7-8节',
                '204820' => '第47章 加拿大教会',
                '204821' => '第48章 教会努力保守信仰',
                '204822' => '第49章 教会联盟与联合',
                '204823' => '第50章 回顾与前瞻',
            ];

            $albums['贤德的妻子(玛莎·佩斯)'] = [
                '205459' => '十周年序-《贤德的妻子》',
                '205460' => '前言',
                '205461' => '第01章 贤德的妻子谁可得着？',
                '205462' => '第02章 妻子对神的认识',
                '205465' => '第03章 妻子对罪的认识',
                '205466' => '第04章 妻子对人际关系的认识',
                '205467' => '第05章 妻子对婚姻的认识',
                '205468' => '第06章 妻子对自己角色的认识',
                '205469' => '第07章 妻子敬拜基督的责任',
                '205470' => '第08章 妻子服侍家人的责任',
                '205471' => '第09章 妻子爱的责任1(原则1、2)',
                '205472' => '第09章 妻子爱的责任2(原则3)',
                '205473' => '第09章 妻子爱的责任3(原则4、5)',
                '205474' => '第10章 妻子敬重丈夫的责任',
                '205475' => '第11章 妻子对亲密关系的责任',
                '205478' => '第12章 妻子的顺服与喜乐',
                '205479' => '第13章 妻子顺服丈夫的圣经原则',
                '205480' => '第14章 神对顺服妻子的保护1',
                '205481' => '第14章 神对顺服妻子的保护2',
                '205482' => '第14章 神对顺服妻子的保护3',
                '205483' => '第15章 妻子顺服丈夫的动力',
                '205484' => '第16章 妻子的沟通之道',
                '205485' => '第17章 妻子的化解冲突之道',
                '205486' => '第18章 妻子的怒气',
                '205487' => '第19章 妻子的恐惧',
                '205488' => '第20章 妻子的孤独',
                '205489' => '第21章 妻子的忧伤',
                '205490' => '附言',
                '205491' => '附录1 救恩问答',
                '205492' => '附录2 生命的更新',
                '205493' => '附录3 作者问答',
                '205496' => '附录4 透过圣经看权柄',
                '205497' => '附录5 你有一颗温柔安静的心吗？',
                '205498' => '附录6 不要照愚昧人的愚妄话回答',
                '205499' => '附录7 关于孤独',
                '205500' => '附录8 神的属性及圣经倡导的顺服',
                '205502' => '附录9 对妻子顺服丈夫的误解',
                '205503' => '附录10 与不信的丈夫携手人生',
            ];

            $albums['中国家庭教会史(王怡)'] = [
                '23313' => '01 新教入华两世纪',
                '23314' => '02 中华归主五十年',
                '23315' => '03 护教士与叛教者',
                '23316' => '04 十年生死两茫茫',
                '23317' => '05 基要派的大复兴',
                '23318' => '06 民主败北，福音进城',
                '23319' => '07 山上之城 初露微光',
                '23320' => '08 在复兴中逼迫 在逼迫中复兴',
                '23322' => '09 改革宗在中国',
                '23323' => '10 家庭教会的 传统、承继与未来',
            ];

            $albums['讲道与讲道的人(钟马田)'] = [
                '223323' => '00 开场序言',
                '223325' => '01 讲道的首要地位',
                '223326' => '02 无可取代',
                '223328' => '03 讲章与讲道',
                '223327' => '04 讲章的形式',
                '223329' => '05 讲道的执行',
                '223330' => '06 讲道的人',
                '223331' => '07 会众',
                '223332' => '08 信息的性质',
                '223333' => '09 讲道者的预备',
                '223334' => '10 讲章的预备',
                '223335' => '11 讲章的成型',
                '223336' => '12 例证、雄辩与幽默感',
                '223337' => '13 应该避免的事项',
                '223338' => '14 决志的呼召',
                '223340' => '15 隐患与神奇',
                '223341' => '16 圣灵和大能的明证',
            ];

            if ($keyword === '782') {
                $content = "=====MENU====\n";
                foreach (array_keys($albums) as $key => $value) {
                    $content .= '【782'.str_pad($key + 1, 2, '0', STR_PAD_LEFT)."】$value\n";
                }

                return ResourceResponse::text(['content' => $content]);
            }

            $cacheKey = "xbot.keyword.hland.{$keyword}";
            $data = Cache::get($cacheKey);

            if (! $data) {
                try {
                    $albumIndex = substr($keyword, 3) - 1;
                    $keys = array_keys($albums);

                    if (! isset($keys[$albumIndex])) {
                        return null;
                    }

                    $oriTitle = $keys[$albumIndex];
                    $album = $albums[$keys[$albumIndex]];
                    $total = count($album);
                    $index = date('z') % $total;

                    $keys = array_keys($album);
                    $blogId = $keys[$index];
                    $title = '【'.($index + 1).'/'.$total.'】'.$oriTitle;
                    $description = $album[$blogId];

                    $audioUrl = config('x-resources.r2_share_audio')."/hland/$oriTitle/$blogId.mp3";

                    $data = [
                        'url' => $audioUrl,
                        'title' => $title,
                        'description' => $description,
                    ];

                    Cache::put($cacheKey, $data, strtotime('tomorrow') - time());
                } catch (\Exception $e) {
                    return null;
                }
            }

            return ResourceResponse::music($data, [
                'metric' => class_basename(__CLASS__),
                'keyword' => $keyword,
            ]);
        }

        return null;
    }
}
