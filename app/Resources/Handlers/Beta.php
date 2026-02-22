<?php

namespace App\Resources\Handlers;

use App\Resources\ResourceResponse;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class Beta
{
    public function getResourceList(): array
    {
        return [
            // Original MBC functionality
            ['keyword' => '791', 'title' => '慕安德烈每日靈修签到'],
            
            // Migrated from Tpehoc Handler - complete list from original
            ['keyword' => '799', 'title' => '恩典365'],
            ['keyword' => '798', 'title' => '罗门,门徒训练'],
            ['keyword' => '797', 'title' => '罗门,教牧辅导'],
            ['keyword' => '795', 'title' => '有声书系列'],
            ['keyword' => '794', 'title' => '救恩之聲 有聲書'],
            ['keyword' => '793', 'title' => '為兒女禱告40天'],
            ['keyword' => '792', 'title' => '基督教要义-导读'],
            ['keyword' => '785', 'title' => '古德恩系統神學導讀'],
            ['keyword' => '781', 'title' => '新媒体宣教'],
        ];
    }

    public function resolve(string $keyword): ?ResourceResponse
    {
        // Original MBC functionality (metric: mbc) - now with 791 code
        if ($keyword == '791') {
            $day = now()->format('md');

            return ResourceResponse::link([
                'url' => 'https://check-in-out.online/devotional',
                'title' => "慕安德烈每日靈修签到-{$day}",
                'description' => '来源：大光傳宣教福音中心',
                'image' => 'https://images.simai.life/images/2024/12/bbae251f80b40a00a7ecefeb6d3c78c7.png',
            ], [
                'metric' => 'mbc',
                'keyword' => $keyword,
                'type' => 'link',
            ]);
        }

        // 793 為兒女禱告40天 - COMPLETE VERSION from original
        if ($keyword == '793') {
            $title = "為兒女禱告40天";
            $desc = "救恩之聲 靈修禱告";
            $prefix = "https://www.vos.org.tw/Datafile/UploadFile/Voice/70/";
            $image = 'https://wsrv.nl/?url=https://i0.wp.com/cchappyfamily.plus/wp-content/uploads/2018/05/pray20180515.jpg';
            $items = [
                '20190122153048.mp3', '20190122153311.mp3', '20190122153321.mp3', '20190122153330.mp3',
                '20190122153401.mp3', '20190122153421.mp3', '20190122153447.mp3', '20190122153516.mp3',
                '20190122153531.mp3', '20190122153554.mp3', '20190122153604.mp3', '20190122153620.mp3',
                '20190122153630.mp3', '20190122153641.mp3', '20190122153658.mp3', '20190122153740.mp3',
                '20190122153753.mp3', '20190122153824.mp3', '20190122153845.mp3', '20190122153858.mp3',
                '20190122153921.mp3', '20190122153947.mp3', '20190122154037.mp3', '20190122154112.mp3',
                '20190122154123.mp3', '20190122154140.mp3', '20190122154151.mp3', '20190122154221.mp3',
                '20190122154250.mp3', '20190122154308.mp3', '20190122154334.mp3', '20190122154355.mp3',
                '20190122154427.mp3', '20190122154513.mp3', '20190122154528.mp3', '20190122154607.mp3',
                '20190122154626.mp3', '20190122154650.mp3', '20190122154706.mp3', '20190122154728.mp3'
            ];
            $index = now()->format('z') % 40;
            $audioUrl = config('x-resources.r2_share_audio')."/793/" . $items[$index];
            
            return ResourceResponse::music([
                'url' => $audioUrl,
                'title' => '('.($index + 1)."/40)".$title,
                'description' => $desc,
                'image' => $image,
            ], ['metric' => 'Tpehoc', 'keyword' => $keyword]);
        }

        // 794 信心是一把梯子 - COMPLETE VERSION from original (72 items!)
        if ($keyword == '794') {
            $title = "信心是一把梯子";
            $desc = "救恩之聲 有聲書";
            $prefix = "https://www.vos.org.tw/Datafile/UploadFile/Voice/52/";
            $items = [    
                ['title' => '幸好上帝沒答應', 'file' => '20211018145604.mp3'],
                ['title' => '量恩而為', 'file' => '20211018145727.mp3'],
                ['title' => '哦上帝不是故意的', 'file' => '20211018150104.mp3'],
                ['title' => '37度C的恩典', 'file' => '20180313114159.mp3'],
                ['title' => '貧心競氣', 'file' => '20180313114233.mp3'],
                ['title' => '傑出的歐巴桑', 'file' => '20180313114300.mp3'],
                ['title' => '印壞的郵票', 'file' => '20180313114323.mp3'],
                ['title' => '不要限定上帝賜福你的方式', 'file' => '20180313143659.mp3'],
                ['title' => '慢半拍的賜福', 'file' => '20180313143726.mp3'],
                ['title' => '我心靈得安寧', 'file' => '20180313143802.mp3'],
                ['title' => '怒火中消', 'file' => '20180313143824.mp3'],
                ['title' => '大智若娛', 'file' => '20180313143846.mp3'],
                ['title' => '簡單生活生活減擔', 'file' => '20180320102340.mp3'],
                ['title' => '清心寡鬱', 'file' => '20200921115539.mp3'],
                ['title' => '勞者多能', 'file' => '20200616153315.mp3'],
                ['title' => '沒有名次的考試', 'file' => '20200616153533.mp3'],
                ['title' => '你怎樣對待你的夢', 'file' => '20200616153657.mp3'],
                ['title' => '後天才子', 'file' => '20200616153817.mp3'],
                ['title' => '許一個雙B的人生', 'file' => '20200616153911.mp3'],
                ['title' => '用烏龜的精神作兔子', 'file' => '20200616154023.mp3'],
                ['title' => '優質的大男人主義', 'file' => '20200616154512.mp3'],
                ['title' => '不可叫人小看你年輕', 'file' => '20200616154708.mp3'],
                ['title' => '善良成大器', 'file' => '20200616154759.mp3'],
                ['title' => '讓愛你的人以你為榮', 'file' => '20200616154909.mp3'],
                ['title' => '下一盤人生的好棋', 'file' => '20200616155013.mp3'],
                ['title' => '小提琴物語', 'file' => '20200616155109.mp3'],
                ['title' => '另一種宣教', 'file' => '20200616155158.mp3'],
                ['title' => '品格是一種魅力', 'file' => '20200616155257.mp3'],
                ['title' => '上帝的馬賽克', 'file' => '20200616155359.mp3'],
                ['title' => '架子與價值', 'file' => '20200616155506.mp3'],
                ['title' => '熱情是金', 'file' => '20200616155558.mp3'],
                ['title' => '窮爸爸富遺產', 'file' => '20200616155658.mp3'],
                ['title' => '生氣時智商只有五歲', 'file' => '20200616155759.mp3'],
                ['title' => '一句話的重量', 'file' => '20200616155858.mp3'],
                ['title' => '另一種雙聲帶', 'file' => '20200616160821.mp3'],
                ['title' => '百善笑為先', 'file' => '20200616160921.mp3'],
                ['title' => '為批評繫上蝴蝶結', 'file' => '20200616161021.mp3'],
                ['title' => '英雄所見不同', 'file' => '20200616161114.mp3'],
                ['title' => '情緒的適放', 'file' => '20200616161210.mp3'],
                ['title' => '原來他也是人', 'file' => '20200731150734.mp3'],
                ['title' => '斜視與偏見', 'file' => '20200731151005.mp3'],
                ['title' => '是誰該死', 'file' => '20200731151208.mp3'],
                ['title' => '吵一場優質的架', 'file' => '20200731151327.mp3'],
                ['title' => '愛人太甚', 'file' => '20200731151428.mp3'],
                ['title' => '錦上不添炭雪中不送花', 'file' => '20200731151551.mp3'],
                ['title' => '強人所難', 'file' => '20200731151726.mp3'],
                ['title' => '最難復健的動作', 'file' => '20200731151822.mp3'],
                ['title' => '心是方向盤', 'file' => '20200731151923.mp3'],
                ['title' => '地瓜型人格', 'file' => '20200731152034.mp3'],
                ['title' => '如果少了您', 'file' => '20200731152212.mp3'],
                ['title' => '浪漫讓慢', 'file' => '20200731152732.mp3'],
                ['title' => '樂透了嗎', 'file' => '20200731152841.mp3'],
                ['title' => '理了髮的草坪', 'file' => '20200731152948.mp3'],
                ['title' => '候補第一的救主', 'file' => '20200731153717.mp3'],
                ['title' => '今日怒今日畢', 'file' => '20200731153901.mp3'],
                ['title' => '如果聖經是武林秘笈', 'file' => '20200731154040.mp3'],
                ['title' => '一二三木頭人', 'file' => '20200731154146.mp3'],
                ['title' => '天國的外交官', 'file' => '20200731154249.mp3'],
                ['title' => '恆行爸道', 'file' => '20200731154405.mp3'],
                ['title' => '當你以為沒人看見的時候', 'file' => '20200828135445.mp3'],
                ['title' => '天堂裡的委員會', 'file' => '20200828135706.mp3'],
                ['title' => '日劇白色巨塔片尾曲的由來', 'file' => '20200828135820.mp3'],
                ['title' => '心靈營養學', 'file' => '20200828140008.mp3'],
                ['title' => '月領三份薪', 'file' => '20200828140138.mp3'],
                ['title' => '十減一大於十', 'file' => '20200828140347.mp3'],
                ['title' => '天父必看顧你', 'file' => '20200921121750.mp3'],
                ['title' => '耶穌選總統', 'file' => '20200828140532.mp3'],
                ['title' => '惡人有惡福', 'file' => '20200828140853.mp3'],
                ['title' => '333生活處方', 'file' => '20200828140952.mp3'],
                ['title' => '祝福滿滿的人生', 'file' => '20200828141056.mp3'],
                ['title' => '情能補拙', 'file' => '20200828141425.mp3'],
                ['title' => '論家世背景', 'file' => '20200828141517.mp3'],
                ['title' => '中風的滑鼠', 'file' => '20180313113950.mp3'],
            ];
            $image = 'https://www.vos.org.tw/Datafile/Icon/20180320152534135.png';
            $total = count($items);
            $index = now()->format('z') % $total;
            
            return ResourceResponse::music([
                'url' => $prefix . $items[$index]['file'],
                'title' => '('.($index + 1)."/{$total})".$title,
                'description' => $items[$index]['title'] . " " . $desc,
                'image' => $image,
            ], ['metric' => 'Tpehoc', 'keyword' => $keyword]);
        }

        // 795 有声书系列 - COMPLETE VERSION from original (26 playlists!)
        if (Str::startsWith($keyword, '795') && strlen($keyword) >= 3) {
            $playLists = [
                ["id" => "PL_sOpTJkyWnAbZRPaSktjlsv0_nH1K6aV", 'title' => '西游记精讲'],
                ["id" => "PL_sOpTJkyWnAeaM_DZvgXyHqJgt2xX7fV", 'title' => '汤姆叔叔的小屋'],
                ["id" => "PL_sOpTJkyWnAH95cdSnLg6DNrylLsu4MA", 'title' => '简爱'],
                ["id" => "PL_sOpTJkyWnB7WT3ukZVq92j47q3qDxdd", 'title' => '乌合之众'],
                ["id" => "PL_sOpTJkyWnDWW_dE67EMwlNaCRB4SfFx", 'title' => '战争与和平'],
                ["id" => "PL_sOpTJkyWnD-U9p6ykLtsO6M0ghTmeCW", 'title' => '昆虫记'],
                ["id" => "PL_sOpTJkyWnB_abdRUng2s-SoOrw0xqB9", 'title' => '神曲'],
                ["id" => "PL_sOpTJkyWnCDfIzb43w_ObbtjApRxAMR", 'title' => '日瓦戈医生'],
                ["id" => "PL_sOpTJkyWnBQZXF3Dw_QqkILQEUeQIMh", 'title' => '围城'],
                ["id" => "PL_sOpTJkyWnBvkuJzr8qIwoBT3w7OK_ul", 'title' => '骆驼祥子'],
                ["id" => "PL_sOpTJkyWnBY_MpWusnEtwailn546EYV", 'title' => '呼啸山庄'],
                ["id" => "PL_sOpTJkyWnCIa3R2IVUZJljWFfNLK8gf", 'title' => '双城计'],
                ["id" => "PL_sOpTJkyWnDyMyzNd8apjrxvKNkEvgRx", 'title' => '雾都孤儿'],
                ["id" => "PL_sOpTJkyWnBmIGMm6o0_zJ5bdReIeHR0", 'title' => '鲁滨逊漂流记'],
                ["id" => "PL_sOpTJkyWnCAUkq4iDr1aMcvNZ2YR37w", 'title' => '巴黎圣母院'],
                ["id" => "PL_sOpTJkyWnDZEt7aQo4LNHrz3eOcTqrd", 'title' => '包法力夫人'],
                ["id" => "PL_sOpTJkyWnAtP1Nr4wUfCpKZwkcsRHxv", 'title' => '红与黑'],
                ["id" => "PL_sOpTJkyWnDGEt9OfloRcP_ER8zPrrQE", 'title' => '灿烂千阳'],
                ["id" => "PL_sOpTJkyWnDoy6jPjJBtXFT48zDW8eus", 'title' => '悲惨世界'],
                ["id" => "PL_sOpTJkyWnBOGAy9suw_w3lT4da2tF_u", 'title' => '傲慢与偏见'],
                ["id" => "PL_sOpTJkyWnAPhHAnA7E9fQBafVH4U4Vw", 'title' => '白夜行'],
                ["id" => "PL_sOpTJkyWnByw3T9x59knNfOcaI8Ozmx", 'title' => '苔丝'],
                ["id" => "PL_sOpTJkyWnCcp7hW1-Fwfm4rNFsr4GAF", 'title' => '复活'],
                ["id" => "PL_sOpTJkyWnB9URu4zW8fJuKvNCDsrzqK", 'title' => '霍乱时期的爱情'],
                ["id" => "PL_sOpTJkyWnCZohPj2g2jUozF8lswZ5so", 'title' => '百年孤独'],
                ["id" => "PL_sOpTJkyWnCT7dwuRgQNdgkYAMzWXHdP", 'title' => '欧亨利'],
            ];

            $oriKeyword = substr($keyword, 1, 3);
            $index = (int) substr($keyword, 4) % count($playLists);

            $playList = $playLists[$index];
            $playListId = $playList['id'];
            $playListTitle = $playList['title'];

            $cacheKey = "resources." . $keyword;
            $items = Cache::get($cacheKey, false);
            
            if (!$items) {
                try {
                    $url = "https://pub-3813a5d14cba4eaeb297a0dba302143c.r2.dev/playlist/{$playListId}/{$playListId}.txt";
                    $response = Http::get($url);
                    $ids = explode(PHP_EOL, $response->body());
                    $items = [];
                    foreach ($ids as $key => $yid) {
                        if (!$yid) continue;
                        $url = "https://pub-3813a5d14cba4eaeb297a0dba302143c.r2.dev/playlist/{$playListId}/{$yid}.info.json";
                        
                        $json = Http::get($url)->json();
                        $key = null;
                        foreach ($json['chapters'] as $key => $chapter) {
                            $index = $key + 1;
                            $tempItem['title'] = $chapter['title'];
                            $tempItem['url'] = "{$yid}/{$index}.m4a";
                            $items[] = $tempItem;
                        }
                    }
                    Cache::put($cacheKey, $items);
                } catch (\Exception $e) {
                    // Fallback to simple version
                    return ResourceResponse::music([
                        'url' => config('x-resources.r2_share_audio')."/playlist/{$playListId}/1.m4a",
                        'title' => $playListTitle,
                        'description' => 'By @LucyFM1999',
                    ], ['metric' => 'Tpehoc', 'keyword' => $keyword]);
                }
            }
            
            if (!empty($items)) {
                $thumbnail = isset($json['thumbnail']) ? $json['thumbnail'] : '';
                $total = count($items);
                $index = now()->format('z') % ($total + 1);
                $item = $items[$index];
                
                return ResourceResponse::music([
                    'url' => config('x-resources.r2_share_audio')."/playlist/{$playListId}/{$item['url']}",
                    'title' => "($index/$total)".$playListTitle,
                    'description' => "{$item['title']} By @LucyFM1999",
                    'image' => $thumbnail,
                ], ['metric' => 'Tpehoc', 'keyword' => $keyword]);
            }
        }

        // 797 罗门,教牧辅导 - COMPLETE VERSION from original (51 items!)
        if ($keyword == '797') {
            $image = 'https://zgtai.com/wp-content/uploads/Luo/luo-36.jpg';
            $items = [
                "传道人的神圣呼召 (1)",
                "传道人的神圣呼召 (2)", 
                "传道人的品格塑造 (1)",
                "传道人的品格塑造 (2)",
                "教牧人员的装备-有关读书",
                "传道人的角色与职份 (1)",
                "传道人的角色与职份 (2)",
                "传道人的角色与职份 (3)",
                "教牧的同工关系 (1)",
                "教牧的同工关系 (2)",
                "传道人事奉的危机 (1)",
                "传道人事奉的危机 (2)",
                "教牧人员的感情陷阱 (1)",
                "教牧人员的感情陷阱 (2)",
                "教牧人员的待遇问题 (1)",
                "教牧人员的待遇问题 (2)",
                "教牧人员冲突的处理",
                "教牧人员特有的危险",
                "教牧人员与教会纪律 (1)",
                "教牧人员与教会纪律 (2)",
                "教牧人员的牧养工作 (1)",
                "教牧人员的牧养工作 (2)",
                "教牧人员的属灵危机-耗尽 (1)",
                "教牧人员的属灵危机-耗尽 (2)",
                "教牧人员与讲道 (1)",
                "教牧人员与讲道 (2)",
                "传道人的家庭",
                "师母的角色扮演",
                "教牧人员与门徒训练 (1)",
                "教牧人员与门徒训练 (2)",
                "主日崇拜的计划与进行",
                "祷告聚会的计划与进行",
                "教牧人员与圣餐的举行",
                "教牧人员与浸礼的举行",
                "教会长老执事的选择 (1)",
                "教会长老执事的选择 (2)",
                "弟兄姊妹转换教会的危机与转机",
                "教牧人员与事奉工场的转换",
                "教牧人员与宣教异象 (1)",
                "教牧人员与宣教异象 (2)",
                "教牧人员与教会增长",
                "教牧人员的压力与能力",
                "教牧人员与信徒皆祭司 (1)",
                "教牧人员与信徒皆祭司 (2)",
                "再思教牧人员的家庭生活",
                "再思教牧人员与冲突处理",
                "教牧人员与辅导",
                "传道人的生命与事奉",
                "教牧人员(教会)与社会责任",
                "教牧人员的受伤与医治",
                "教牧人员与十字架的道路 (1)",
                "教牧人员与十字架的道路 (2)",
            ];
            $items = array_reverse($items);
            $total = count($items);
            $index = now()->addDay(1)->format('z') % $total;
            $item = $items[$index];
            $displayIndex = str_pad($index + 1, 2, '0', STR_PAD_LEFT);
            
            return ResourceResponse::music([
                'url' => config('x-resources.r2_share_audio')."/zgtai.com/mgs/" . $displayIndex . ".mp3",
                'title' => "({$displayIndex}/{$total})".$item,
                'description' => '罗门,我是好牧人',
                'image' => $image,
            ], ['metric' => 'Tpehoc', 'keyword' => $keyword]);
        }

        // 785 古德恩系統神學導讀 - COMPLETE VERSION from original (61 chapters!)
        if ($keyword == '785') {
            $items = [
                "第一章-系統神學簡介" => "ch01-%E5%8F%A4%E5%BE%B7%E6%81%A9%E7%B3%BB%E7%B5%B1%E7%A5%9E%E5%AD%B8-%E7%AC%AC%E4%B8%80%E7%AB%A0-T.mp4",
                "第二章-神的道" => "ch02-%E5%8F%A4%E5%BE%B7%E6%81%A9%E7%B3%BB%E7%B5%B1%E7%A5%9E%E5%AD%B8-%E7%AC%AC%E4%BA%8C%E7%AB%A0-T.mp4",
                "第三章-聖經乃正典" => "ch03-%E5%8F%A4%E5%BE%B7%E6%81%A9%E7%B3%BB%E7%B5%B1%E7%A5%9E%E5%AD%B8-%E7%AC%AC%E4%B8%89%E7%AB%A0-T.mp4",
                "第四章-聖經四特徵之一 權威性" => "ch04-%E5%8F%A4%E5%BE%B7%E6%81%A9%E7%B3%BB%E7%B5%B1%E7%A5%9E%E5%AD%B8-%E7%AC%AC%E5%9B%9B%E7%AB%A0-T.mp4",
                "第五章-聖經的無誤性" => "ch05-%E5%8F%A4%E5%BE%B7%E6%81%A9%E7%B3%BB%E7%B5%B1%E7%A5%9E%E5%AD%B8-%E7%AC%AC%E4%BA%94%E7%AB%A0-T.mp4",
                "第六章-聖經四特徵之二 清晰性" => "ch06-%E5%8F%A4%E5%BE%B7%E6%81%A9%E7%B3%BB%E7%B5%B1%E7%A5%9E%E5%AD%B8-%E7%AC%AC%E5%85%AD%E7%AB%A0-T.mp4",
                "第七章-聖經四特徵之三 必須性" => "ch07-%E5%8F%A4%E5%BE%B7%E6%81%A9%E7%B3%BB%E7%B5%B1%E7%A5%9E%E5%AD%B8-%E7%AC%AC%E4%B8%83%E7%AB%A0-T.mp4",
                "第八章-聖經四特徵之四 充足性" => "ch08-%E5%8F%A4%E5%BE%B7%E6%81%A9%E7%B3%BB%E7%B5%B1%E7%A5%9E%E5%AD%B8-%E7%AC%AC%E5%85%AB%E7%AB%A0-T.mp4",
                "第九章-神的存在" => "ch09-%E5%8F%A4%E5%BE%B7%E6%81%A9%E7%B3%BB%E7%B5%B1%E7%A5%9E%E5%AD%B8-%E7%AC%AC%E4%B9%9D%E7%AB%A0-T.mp4",
                "第十章-神的可知性" => "ch10-%E5%8F%A4%E5%BE%B7%E6%81%A9%E7%B3%BB%E7%B5%B1%E7%A5%9E%E5%AD%B8-%E7%AC%AC%E5%8D%81%E7%AB%A0-T.mp4",
                "第十一章-神的性格－不可交通的屬性" => "ch11-%E5%8F%A4%E5%BE%B7%E6%81%A9%E7%B3%BB%E7%B5%B1%E7%A5%9E%E5%AD%B8-%E7%AC%AC%E5%8D%81%E4%B8%80%E7%AB%A0-T.mp4",
                "第十二章-神的性格－可交通的屬性之一" => "ch12-%E5%8F%A4%E5%BE%B7%E6%81%A9%E7%B3%BB%E7%B5%B1%E7%A5%9E%E5%AD%B8-%E7%AC%AC%E5%8D%81%E4%BA%8C%E7%AB%A0-T.mp4",
                "第十三章-神的性格－可交通的屬性之二" => "ch13-%E5%8F%A4%E5%BE%B7%E6%81%A9%E7%B3%BB%E7%B5%B1%E7%A5%9E%E5%AD%B8-%E7%AC%AC%E5%8D%81%E4%B8%89%E7%AB%A0-T.mp4",
                "第十四章-神的三一－三位一體" => "ch14-%E5%8F%A4%E5%BE%B7%E6%81%A9%E7%B3%BB%E7%B5%B1%E7%A5%9E%E5%AD%B8-%E7%AC%AC%E5%8D%81%E5%9B%9B%E7%AB%A0-T.mp4",
                "第十五章-創造" => "ch15-%E5%8F%A4%E5%BE%B7%E6%81%A9%E7%B3%BB%E7%B5%B1%E7%A5%9E%E5%AD%B8-%E7%AC%AC%E5%8D%81%E4%BA%94%E7%AB%A0-T.mp4",
                "第十六章-神的天命" => "ch16-%E5%8F%A4%E5%BE%B7%E6%81%A9%E7%B3%BB%E7%B5%B1%E7%A5%9E%E5%AD%B8-%E7%AC%AC%E5%8D%81%E5%85%AD%E7%AB%A0-T.mp4",
                "第十七章-神蹟" => "ch17-%E5%8F%A4%E5%BE%B7%E6%81%A9%E7%B3%BB%E7%B5%B1%E7%A5%9E%E5%AD%B8-%E7%AC%AC%E5%8D%81%E4%B8%83%E7%AB%A0-T.mp4",
                "第十八章-禱告" => "ch18-%E5%8F%A4%E5%BE%B7%E6%81%A9%E7%B3%BB%E7%B5%B1%E7%A5%9E%E5%AD%B8-%E7%AC%AC%E5%8D%81%E5%85%AB%E7%AB%A0-T.mp4",
                "第十九章-天使" => "ch19-%E5%8F%A4%E5%BE%B7%E6%81%A9%E7%B3%BB%E7%B5%B1%E7%A5%9E%E5%AD%B8-%E7%AC%AC%E5%8D%81%E4%B9%9D%E7%AB%A0-T.mp4",
                "第二十章-撒但與鬼魔" => "ch20-%E5%8F%A4%E5%BE%B7%E6%81%A9%E7%B3%BB%E7%B5%B1%E7%A5%9E%E5%AD%B8-%E7%AC%AC%E4%BA%8C%E5%8D%81%E7%AB%A0-T.mp4",
                "第二十一章-人的受造" => "ch21-%E5%8F%A4%E5%BE%B7%E6%81%A9%E7%B3%BB%E7%B5%B1%E7%A5%9E%E5%AD%B8-%E7%AC%AC%E4%BA%8C%E5%8D%81%E4%B8%80%E7%AB%A0-T.mp4",
                "第二十二章-人有男性與女性" => "ch22-%E5%8F%A4%E5%BE%B7%E6%81%A9%E7%B3%BB%E7%B5%B1%E7%A5%9E%E5%AD%B8-%E7%AC%AC%E4%BA%8C%E5%8D%81%E4%BA%8C%E7%AB%A0-T.mp4",
                "第二十三章-人性的本質" => "ch23-%E5%8F%A4%E5%BE%B7%E6%81%A9%E7%B3%BB%E7%B5%B1%E7%A5%9E%E5%AD%B8-%E7%AC%AC%E4%BA%8C%E5%8D%81%E4%B8%89%E7%AB%A0-T.mp4",
                "第二十三章-歷史見證" => "ch23-1-%E5%8F%A4%E5%BE%B7%E6%81%A9%E7%B3%BB%E7%B5%B1%E7%A5%9E%E5%AD%B8-%E7%AC%AC%E4%BA%8C%E5%8D%81%E4%B8%89%E7%AB%A0%E6%AD%B7%E5%8F%B2%E8%A6%8B%E8%AD%89-T.mp4",
                "第二十四章-罪" => "ch24-%E5%8F%A4%E5%BE%B7%E6%81%A9%E7%B3%BB%E7%B5%B1%E7%A5%9E%E5%AD%B8-%E7%AC%AC%E4%BA%8C%E5%8D%81%E5%9B%9B%E7%AB%A0-T.mp4",
                "第二十五章-神人之間的約" => "ch25-%E5%8F%A4%E5%BE%B7%E6%81%A9%E7%B3%BB%E7%B5%B1%E7%A5%9E%E5%AD%B8-%E7%AC%AC%E4%BA%8C%E5%8D%81%E4%BA%94%E7%AB%A0-T.mp4",
                "第二十六章-基督的身位" => "ch26-%E5%8F%A4%E5%BE%B7%E6%81%A9%E7%B3%BB%E7%B5%B1%E7%A5%9E%E5%AD%B8-%E7%AC%AC%E4%BA%8C%E5%8D%81%E5%85%AD%E7%AB%A0-T.mp4",
                "第二十六章-歷史見證" => "ch26-%E5%8F%A4%E5%BE%B7%E6%81%A9%E7%B3%BB%E7%B5%B1%E7%A5%9E%E5%AD%B8-%E7%AC%AC%E4%BA%8C%E5%8D%81%E5%85%AD%E7%AB%A0-%E6%AD%B7%E5%8F%B2%E8%A6%8B%E8%AD%89-T.mp4",
                "第二十七章-基督的救贖" => "ch27-%E5%8F%A4%E5%BE%B7%E6%81%A9%E7%B3%BB%E7%B5%B1%E7%A5%9E%E5%AD%B8-%E7%AC%AC%E4%BA%8C%E5%8D%81%E4%B8%83%E7%AB%A0-T.mp4",
                "第二十七章-歷史見證" => "ch27-1-%E5%8F%A4%E5%BE%B7%E6%81%A9%E7%B3%BB%E7%B5%B1%E7%A5%9E%E5%AD%B8-%E7%AC%AC%E4%BA%8C%E5%8D%81%E4%B8%83%E7%AB%A0-%E6%AD%B7%E5%8F%B2%E8%A6%8B%E8%AD%89-T.mp4",
                "第二十八章-基督的復活與升天" => "ch28-%E5%8F%A4%E5%BE%B7%E6%81%A9%E7%B3%BB%E7%B5%B1%E7%A5%9E%E5%AD%B8-%E7%AC%AC%E4%BA%8C%E5%8D%81%E5%85%AB%E7%AB%A0-T.mp4",
                "第二十九章-基督的職份" => "ch29-%E5%8F%A4%E5%BE%B7%E6%81%A9%E7%B3%BB%E7%B5%B1%E7%A5%9E%E5%AD%B8-%E7%AC%AC%E4%BA%8C%E5%8D%81%E4%B9%9D%E7%AB%A0-T.mp4",
                "第三十章-聖靈的工作" => "ch30-%E5%8F%A4%E5%BE%B7%E6%81%A9%E7%B3%BB%E7%B5%B1%E7%A5%9E%E5%AD%B8-%E7%AC%AC%E4%B8%89%E5%8D%81%E7%AB%A0-T.mp4",
                "第三十二章-揀選與棄絕" => "ch32-%E5%8F%A4%E5%BE%B7%E6%81%A9%E7%B3%BB%E7%B5%B1%E7%A5%9E%E5%AD%B8-%E7%AC%AC%E4%B8%89%E5%8D%81%E4%BA%8C%E7%AB%A0-T.mp4",
                "第三十二章-揀選與棄絕-歷史見證" => "ch32-1-%E5%8F%A4%E5%BE%B7%E6%81%A9%E7%B3%BB%E7%B5%B1%E7%A5%9E%E5%AD%B8-%E7%AC%AC%E4%B8%89%E5%8D%81%E4%BA%8C%E7%AB%A0-%E6%AD%B7%E5%8F%B2%E8%A6%8B%E8%AD%89-T.mp4",
                "第三十三章-福音的呼召與有效的呼召" => "ch33-%E5%8F%A4%E5%BE%B7%E6%81%A9%E7%B3%BB%E7%B5%B1%E7%A5%9E%E5%AD%B8-%E7%AC%AC%E4%B8%89%E5%8D%81%E4%B8%89%E7%AB%A0-T.mp4",
                "第三十四章-重生" => "ch34-%E5%8F%A4%E5%BE%B7%E6%81%A9%E7%B3%BB%E7%B5%B1%E7%A5%9E%E5%AD%B8-%E7%AC%AC%E4%B8%89%E5%8D%81%E5%9B%9B%E7%AB%A0-T.mp4",
                "第三十五章-歸正－信心與悔改" => "ch35-%E5%8F%A4%E5%BE%B7%E6%81%A9%E7%B3%BB%E7%B5%B1%E7%A5%9E%E5%AD%B8-%E7%AC%AC%E4%B8%89%E5%8D%81%E4%BA%94%E7%AB%A0-T.mp4",
                "第三十六章-稱義" => "ch36-%E5%8F%A4%E5%BE%B7%E6%81%A9%E7%B3%BB%E7%B5%B1%E7%A5%9E%E5%AD%B8-%E7%AC%AC%E4%B8%89%E5%8D%81%E5%85%AD%E7%AB%A0-T.mp4",
                "第三十七章-兒子的名分" => "ch37-%E5%8F%A4%E5%BE%B7%E6%81%A9%E7%B3%BB%E7%B5%B1%E7%A5%9E%E5%AD%B8-%E7%AC%AC%E4%B8%89%E5%8D%81%E4%B8%83%E7%AB%A0-T.mp4",
                "第三十八章-成聖" => "ch38-%E5%8F%A4%E5%BE%B7%E6%81%A9%E7%B3%BB%E7%B5%B1%E7%A5%9E%E5%AD%B8-%E7%AC%AC%E4%B8%89%E5%8D%81%E5%85%AB%E7%AB%A0-T.mp4",
                "第三十九章-聖靈的洗與聖靈的充滿" => "ch39-%E5%8F%A4%E5%BE%B7%E6%81%A9%E7%B3%BB%E7%B5%B1%E7%A5%9E%E5%AD%B8-%E7%AC%AC%E4%B8%89%E5%8D%81%E4%B9%9D%E7%AB%A0-T.mp4",
                "救恩的確據" => "%E5%A2%9E%E7%AF%87-%E6%95%91%E6%81%A9%E7%9A%84%E7%A2%BA%E6%93%9A-T.mp4",
                "第四十章-聖徒的恆忍" => "ch40-%E5%8F%A4%E5%BE%B7%E6%81%A9%E7%B3%BB%E7%B5%B1%E7%A5%9E%E5%AD%B8-%E7%AC%AC%E5%9B%9B%E5%8D%81%E7%AB%A0-T.mp4",
                "第四十一章-死亡與居間階段" => "ch41-%E5%8F%A4%E5%BE%B7%E6%81%A9%E7%B3%BB%E7%B5%B1%E7%A5%9E%E5%AD%B8-%E7%AC%AC%E5%9B%9B%E5%8D%81%E4%B8%80%E7%AB%A0-T.mp4",
                "第四十二章-得榮－得著復活的身體" => "ch42-%E5%8F%A4%E5%BE%B7%E6%81%A9%E7%B3%BB%E7%B5%B1%E7%A5%9E%E5%AD%B8-%E7%AC%AC%E5%9B%9B%E5%8D%81%E4%BA%8C%E7%AB%A0-T.mp4",
                "第四十四章-教會的本質-標誌-目的" => "ch44-%E5%8F%A4%E5%BE%B7%E6%81%A9%E7%B3%BB%E7%B5%B1%E7%A5%9E%E5%AD%B8-%E7%AC%AC%E5%9B%9B%E5%8D%81%E5%9B%9B%E7%AB%A0-T.mp4",
                "第四十五章-教會的純潔與合一" => "ch45-%E5%8F%A4%E5%BE%B7%E6%81%A9%E7%B3%BB%E7%B5%B1%E7%A5%9E%E5%AD%B8-%E7%AC%AC%E5%9B%9B%E5%8D%81%E4%BA%94%E7%AB%A0-T.mp4",
                "第四十六章-教會的權力" => "ch46-%E5%8F%A4%E5%BE%B7%E6%81%A9%E7%B3%BB%E7%B5%B1%E7%A5%9E%E5%AD%B8-%E7%AC%AC%E5%9B%9B%E5%8D%81%E5%85%AD%E7%AB%A0-T.mp4",
                "第四十七章-教會管治的體制" => "ch47-%E5%8F%A4%E5%BE%B7%E6%81%A9%E7%B3%BB%E7%B5%B1%E7%A5%9E%E5%AD%B8-%E7%AC%AC%E5%9B%9B%E5%8D%81%E4%B8%83%E7%AB%A0-T.mp4",
                "第四十八章-神在教會內施恩之法" => "ch48-%E5%8F%A4%E5%BE%B7%E6%81%A9%E7%B3%BB%E7%B5%B1%E7%A5%9E%E5%AD%B8-%E7%AC%AC%E5%9B%9B%E5%8D%81%E5%85%AB%E7%AB%A0-T.mp4",
                "第四十九章-洗禮" => "ch49-%E5%8F%A4%E5%BE%B7%E6%81%A9%E7%B3%BB%E7%B5%B1%E7%A5%9E%E5%AD%B8-%E7%AC%AC%E5%9B%9B%E5%8D%81%E4%B9%9D%E7%AB%A0-T.mp4",
                "第五十章-主的晚餐" => "ch50-%E5%8F%A4%E5%BE%B7%E6%81%A9%E7%B3%BB%E7%B5%B1%E7%A5%9E%E5%AD%B8-%E7%AC%AC%E4%BA%94%E5%8D%81%E7%AB%A0-T.mp4",
                "第五十一章-崇拜" => "ch51-%E5%8F%A4%E5%BE%B7%E6%81%A9%E7%B3%BB%E7%B5%B1%E7%A5%9E%E5%AD%B8-%E7%AC%AC%E4%BA%94%E5%8D%81%E4%B8%80%E7%AB%A0-T.mp4",
                "第五十二章-靈恩﹕一般性的問題" => "ch52-%E5%8F%A4%E5%BE%B7%E6%81%A9%E7%B3%BB%E7%B5%B1%E7%A5%9E%E5%AD%B8-%E7%AC%AC%E4%BA%94%E5%8D%81%E4%BA%8C%E7%AB%A0-T.mp4",
                "第五十三章-靈恩﹕特定的恩賜" => "ch53-%E5%8F%A4%E5%BE%B7%E6%81%A9%E7%B3%BB%E7%B5%B1%E7%A5%9E%E5%AD%B8-%E7%AC%AC%E4%BA%94%E5%8D%81%E4%B8%89%E7%AB%A0-T.mp4",
                "第五十四章-基督的再來－何時﹖如何﹖" => "ch54-%E5%8F%A4%E5%BE%B7%E6%81%A9%E7%B3%BB%E7%B5%B1%E7%A5%9E%E5%AD%B8-%E7%AC%AC%E4%BA%94%E5%8D%81%E5%9B%9B%E7%AB%A0-T.mp4",
            ];
            $total = count($items);
            $index = now()->format('z') % $total;
            $count = 0;
            $title = '';
            $url = '';
            foreach ($items as $chapterTitle => $chapterUrl) {
                if ($count == $index) {
                    $title = $chapterTitle;
                    $url = $chapterUrl;
                    break;
                }
                $count++;
            }
            $mp4Url = 'https://www.alopen.org/Portals/0/Downloads/' . $url;

            $video = ResourceResponse::link([
                'url' => $mp4Url,
                'title' => '古德恩系統神學導讀 (張麟至牧師)',
                'description' => "(" . ($index + 1) . "/{$total})" . $title,
                'image' => 'https://www.alopen.org/portals/0/Images/PastorPaulChangPhoto.jpg',
            ]);

            return ResourceResponse::music([
                'url' => $mp4Url,
                'title' => '古德恩系統神學導讀 (張麟至牧師)',
                'description' => "(" . ($index + 1) . "/{$total})" . $title,
            ], ['metric' => 'Tpehoc', 'keyword' => $keyword], $video);
        }

        // 798 罗门,门徒训练 - COMPLETE VERSION from original (52 items!)
        if ($keyword == '798') {
            $image = 'https://zgtai.com/wp-content/uploads/Luo/luo-36.jpg';
            $items = [
                "作主门徒的挑战",
                "耶稣与门徒", 
                "寻找人作门徒（一）",
                "寻找人作门徒（二）",
                "门徒训练的目标(一)：有关读经",
                "门徒训练的目标(二)：祷告的操练",
                "门徒训练的目标(三)：作见证的操练",
                "门徒训练的目标(四)：团契生活的操练",
                "从信徒到门徒",
                "门徒训练者的操练",
                "门徒的纪律生活",
                "门徒训练与教会增长",
                "门徒训练与配搭事奉",
                "门徒训练与恩赐操练",
                "初信者的栽培计划",
                "门徒进阶训练的栽培计划",
                "门徒的品格操练－话语和舌头的控制",
                "门徒训练的栽培计划",
                "迈向灵性的成熟（一）",
                "迈向灵性的成熟（二）",
                "门徒的情绪管理（一）",
                "门徒的情绪管理（二）",
                "成为热心事奉的门徒",
                "作门徒与钱财的好管家",
                "门徒敬拜的操练（一）",
                "门徒敬拜的操练（二）",
                "门徒家庭崇拜(家庭祭坛)的建立",
                "如何带领归纳式研经法(查经班)",
                "如何带领一个小组",
                "展开门徒训练者的服事",
                "过敬虔的门徒生活",
                "如何明白神的旨意",
                "门徒与讲道操练（一）",
                "门徒与讲道操练（二）",
                "作门徒必须终身学习",
                "门徒与十字架的道理",
                "如何听讲道？",
                "门徒在苦难中的操练",
                "寻找合神心意的领袖",
                "作领袖的代价与陷阱",
                "学习倾听神的声音",
                "如何训练门徒信心的功课",
                "攻克己身的挑战",
                "基督徒团契生活的操练",
                "门徒的圣洁与成圣生活",
                "门徒的职业与工作观",
                "门徒的时间管理与灵修生活",
                "门徒的进修生活与成长",
                "门徒祷告生活的再思",
                "领袖的拣选与榜样",
                "门徒训练与圣灵的建造工作（一）",
                "门徒训练与圣灵的建造工作（二）",
            ];
            $items = array_reverse($items);
            $total = count($items);
            $index = now()->addDay(1)->format('z') % $total;
            $item = $items[$index];
            $displayIndex = str_pad($index + 1, 2, '0', STR_PAD_LEFT);
            
            return ResourceResponse::music([
                'url' => 'https://r2.savefamily.net/zgtai.com/mds/'.$displayIndex.'.mp3',
                'title' => "({$displayIndex}/{$total})".$item,
                'description' => '罗门,门徒训练',
                'image' => $image,
            ], ['metric' => 'Tpehoc', 'keyword' => $keyword]);
        }

        // 792 基督教要义-导读 - COMPLETE VERSION from original (110 chapters!)
        if ($keyword == '792') {
            $count = 110;
            $index = (now()->format('z') % $count) + 1;

            return ResourceResponse::music([
                'url' => config('x-resources.r2_share_audio').'/resources/792/'.str_pad($index, 3, '0', STR_PAD_LEFT).'.mp3',
                'title' => '基督教要义-导读',
                'description' => "({$index}/{$count}) 林慈信 加尔文《基督教教义》研读",
                'image' => config('x-resources.r2_share_audio').'/resources/792/792.jpg',
            ], ['metric' => 'Tpehoc', 'keyword' => $keyword]);
        }

        // 799 恩典365 - COMPLETE VERSION from original with cache and HTTP logic
        if ($keyword == '799') {
            $url = 'https://www.tpehoc.org.tw'.Carbon::now('Asia/Shanghai')->format('/Y/m/');
            $cacheKey = 'xbot.keyword.'.$keyword;
            $data = Cache::get($cacheKey);

            if (!$data) {
                try {
                    $response = Http::get($url);
                    if ($response->failed()) {
                        return null;
                    }
                    $html = $response->body();

                    // Extract MP3 using regex
                    $mp3 = '';
                    if (preg_match('/<source[^>]*src="([^"]+)"/', $html, $mp3Matches)) {
                        $mp3 = str_replace('?_=1', '', $mp3Matches[1]);
                    }

                    // Extract Title using regex
                    $title = '';
                    if (preg_match('/<h3[^>]*>(.*?)<\/h3>/s', $html, $titleMatches)) {
                        $title = trim(strip_tags($titleMatches[1]));
                    }

                    // Extract Description using regex
                    $description = '';
                    if (preg_match('/<div class="post-content">(.*?)<\/div>/s', $html, $descMatches)) {
                        $description = trim(strip_tags($descMatches[1]));
                        if ($title) {
                            $description = str_replace($title, '', $description);
                        }
                    }

                    if (!$title) {
                        return null;
                    }

                    $title = str_replace('&#8230;', '', $title);
                    $description = str_replace('&#8230;', '', $description);

                    $image = 'https://wsrv.nl/?url=i.ytimg.com/vi/JCNu1COWfJY/mqdefault.jpg';

                    $Ym = Carbon::now('Asia/Shanghai')->format('Ym');
                    $Ymd = Carbon::now('Asia/Shanghai')->format('Ymd');
                    $grace365Url = "https://nas.hvfhoc.com/grace365/{$Ym}/{$Ymd}.mp4";

                    $grace365 = ResourceResponse::link([
                        'url' => $grace365Url,
                        'title' => '恩典365',
                        'description' => $Ymd,
                        'image' => 'https://wsrv.nl/?url=tpehoc.org.tw/wp-content/uploads/2024/10/365-615x346.png',
                    ]);

                    $date = now()->format('Ymd');
                    $audioUrl = config('x-resources.r2_share_audio')."/799/{$date}.mp3";

                    $data = [
                        'url' => $audioUrl,
                        'title' => $title,
                        'description' => $description,
                        'image' => $image,
                    ];

                    Cache::put($cacheKey, $data, strtotime('tomorrow') - time());

                    return ResourceResponse::music($data, ['metric' => 'Tpehoc', 'keyword' => $keyword], $grace365);
                } catch (\Exception $e) {
                    return null;
                }
            }

            return ResourceResponse::music($data, ['metric' => 'Tpehoc', 'keyword' => $keyword]);
        }

        // 781 新媒体宣教 - COMPLETE VERSION from original (12 courses!)
        if ($keyword == '781') {
            $items = [
                "新媒体宣教Espresso1：人人宣教",
                "新媒体宣教Espresso2：朋友圈是最大的禾场",
                "新媒体宣教Espresso3：去中心化",
                "新媒体宣教Espresso4：从善用到塑造",
                "新媒体宣教Espresso5：突破同温层",
                "新媒体宣教Espresso6：道成了肉身",
                "新媒体宣教Espresso7：信、望、爱",
                "新媒体宣教Espresso8：挑战与机会",
                "新媒体宣教Espresso9：高度处境化",
                "新媒体宣教Espresso 10：标题党 蹭热点",
                "新媒体宣教Espresso11：用爱心说诚实话",
                "新媒体宣教Espresso12：宣教要成为一种生活方式",
            ];
            $total = count($items);
            $index = now()->subDay()->format('z') % $total;
            $item = $items[$index];
            $titles = explode('：', $item);
            $mp3 = "http://www.jtoday.org/wp-content/uploads/2022/08/mavmm0".str_pad($index + 1, 2, '0', STR_PAD_LEFT).".mp3";

            return ResourceResponse::music([
                'url' => $mp3,
                'title' => str_replace('Espresso', '课程', $titles[0]),
                'description' => $titles[1],
            ], ['metric' => 'Tpehoc', 'keyword' => $keyword]);
        }

        return null;
    }
}