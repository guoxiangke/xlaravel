<?php

namespace App\Resources\Handlers;

use App\Resources\ResourceResponse;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class Tpehoc
{
    public function resolve(string $keyword): ?ResourceResponse
    {
        if ($keyword == '799') {
            $url = 'https://www.tpehoc.org.tw'.Carbon::now('Asia/Shanghai')->format('/Y/m/');
            $cacheKey = 'xbot.keyword.'.$keyword;
            $data = Cache::get($cacheKey);

            if (! $data) {
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

                    // Extract Link URL using regex
                    $linkUrl = '';
                    if (preg_match('/<h3[^>]*>.*?href="([^"]+)".*?<\/h3>/s', $html, $linkMatches)) {
                        $linkUrl = $linkMatches[1];
                    }

                    if (! $title) {
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

                    return ResourceResponse::music($data, [], $grace365);
                } catch (\Exception $e) {
                    return null;
                }
            }

            return ResourceResponse::music($data);
        }

        if ($keyword == '798') {
            $image = 'https://zgtai.com/wp-content/uploads/Luo/luo-36.jpg';
            $items = [
                '作主门徒的挑战', '耶稣与门徒', '寻找人作门徒（一）', '寻找人作门徒（二）', '门徒训练的目标(一)：有关读经',
                '门徒训练的目标(二)：祷告的操练', '门徒训练的目标(三)：作见证的操练', '门徒训练的目标(四)：团契生活的操练', '从信徒到门徒', '门徒训练者的操练',
                '门徒的纪律生活', '门徒训练与教会增长', '门徒训练与配搭事奉', '门徒训练与恩赐操练', '初信者的栽培计划',
                '门徒进阶训练的栽培计划', '门徒的品格操练－话语和舌头的控制', '门徒训练的栽培计划', '迈向灵性的成熟（一）', '迈向灵性的成熟（二）',
                '门徒的情绪管理（一）', '门徒的情绪管理（二）', '成为热心事奉的门徒', '作门徒与钱财的好管家', '门徒敬拜的操练（一）',
                '门徒敬拜的操练（二）', '门徒家庭崇拜(家庭祭坛)的建立', '如何带领归纳式研经法(查经班)', '如何带领一个小组', '展开门徒训练者的服事',
                '过敬虔的门徒生活', '如何明白神的旨意', '门徒与讲道操练（一）', '门徒与讲道操练（二）', '作门徒必须终身学习',
                '门徒与十字架的道理', '如何听讲道？', '门徒在苦难中的操练', '寻找合神心意的领袖', '作领袖的代价与陷阱',
                '学习倾听神的声音', '如何训练门徒信心的功课', '攻克己身的挑战', '基督徒团契生活的操练', '门徒的圣洁与成圣生活',
                '门徒的职业与工作观', '门徒的时间管理与灵修生活', '门徒的进修生活与成长', '门徒祷告生活的再思', '领袖的拣选与榜样',
                '门徒训练与圣灵的建造工作（一）', '门徒训练与圣灵的建造工作（二）',
            ];
            $total = count($items);
            $index = now()->addDay(1)->format('z') % $total;
            $item = $items[$index];
            $displayIndex = str_pad($index + 1, 2, '0', STR_PAD_LEFT);

            return ResourceResponse::music([
                'url' => 'https://r2.savefamily.net/zgtai.com/mds/'.$displayIndex.'.mp3',
                'title' => "({$displayIndex}/{$total})".$item,
                'description' => '罗门,门徒训练',
                'image' => $image,
            ]);
        }

        if ($keyword == '797') {
            $image = 'https://zgtai.com/wp-content/uploads/Luo/luo-36.jpg';
            $items = [
                '教牧人员与十字架的道路 (1)', '教牧人员与十字架的道路 (2)', '教牧人员的受伤与医治', '教牧人员的灵命成长', '教牧人员的自我管理',
                '教牧人员的家庭生活', '教牧人员的呼召与奉献', '教牧人员的角色与职责', '教牧人员的品格与操练', '教牧人员的事奉与挑战',
            ];
            $total = count($items);
            $index = now()->format('z') % $total;
            $item = $items[$index];
            $displayIndex = str_pad($index + 1, 2, '0', STR_PAD_LEFT);

            return ResourceResponse::music([
                'url' => 'https://r2.savefamily.net/zgtai.com/pastor/'.$displayIndex.'.mp3',
                'title' => "({$displayIndex}/{$total})".$item,
                'description' => '罗门,教牧辅导',
                'image' => $image,
            ]);
        }

        if (Str::startsWith($keyword, '795')) {
            $playLists = [
                ['id' => 'PL_sOpTJkyWnAFIdid_v_Z_v_Z_v_Z_v_Z', 'title' => '欧亨利'],
            ];
            $index = (int) substr($keyword, 4) ?: 0;
            $playList = $playLists[$index % count($playLists)];
            $playListId = $playList['id'];

            return ResourceResponse::music([
                'url' => config('x-resources.r2_share_audio')."/playlist/{$playListId}/1.m4a",
                'title' => $playList['title'],
                'description' => 'By @LucyFM1999',
            ]);
        }

        if ($keyword == '794') {
            $items = [
                ['title' => '幸好上帝沒答應', 'file' => '20211018145604.mp3'],
                ['title' => '量恩而為', 'file' => '20211018145727.mp3'],
            ];
            $total = count($items);
            $index = now()->format('z') % $total;
            $item = $items[$index];

            return ResourceResponse::music([
                'url' => 'https://www.vos.org.tw/Datafile/UploadFile/Voice/52/'.$item['file'],
                'title' => '('.($index + 1)."/{$total})信心是一把梯子",
                'description' => $item['title'].' 救恩之聲 有聲書',
                'image' => 'https://www.vos.org.tw/Datafile/Icon/20180320152534135.png',
            ]);
        }

        if ($keyword == '793') {
            $items = ['20190122153048.mp3', '20190122153311.mp3'];
            $total = count($items);
            $index = now()->format('z') % $total;

            return ResourceResponse::music([
                'url' => config('x-resources.r2_share_audio').'/793/'.$items[$index],
                'title' => '('.($index + 1)."/{$total})為兒女禱告40天",
                'description' => '救恩之聲 靈修禱告',
                'image' => 'https://wsrv.nl/?url=https://i0.wp.com/cchappyfamily.plus/wp-content/uploads/2018/05/pray20180515.jpg',
            ]);
        }

        if ($keyword == '792') {
            $count = 110;
            $index = (now()->format('z') % $count) + 1;

            return ResourceResponse::music([
                'url' => config('x-resources.r2_share_audio').'/resources/792/'.str_pad($index, 3, '0', STR_PAD_LEFT).'.mp3',
                'title' => '基督教要义-导读',
                'description' => "({$index}/{$count}) 林慈信 加尔文《基督教教义》研读",
                'image' => config('x-resources.r2_share_audio').'/resources/792/792.jpg',
            ]);
        }

        if ($keyword == '785') {
            $items = ['第一章-系統神學簡介' => 'ch01-%E5%8F%A4%E5%BE%B7%E6%81%A9%E7%B3%BB%E7%B5%B1%E7%A5%9E%E5%AD%B8-%E7%AC%AC%E4%B8%80%E7%AB%A0-T.mp4'];
            $total = count($items);
            $index = now()->format('z') % $total;
            $title = array_keys($items)[$index];
            $url = array_values($items)[$index];
            $mp4Url = 'https://www.alopen.org/Portals/0/Downloads/'.$url;

            $video = ResourceResponse::link([
                'url' => $mp4Url,
                'title' => '古德恩系統神學導讀 (張麟至牧師)',
                'description' => "({$index}/{$total})".$title,
                'image' => 'https://www.alopen.org/portals/0/Images/PastorPaulChangPhoto.jpg',
            ]);

            return ResourceResponse::music([
                'url' => $mp4Url,
                'title' => '古德恩系統神學導讀 (張麟至牧師)',
                'description' => "({$index}/{$total})".$title,
            ], [], $video);
        }

        if ($keyword == '781') {
            $items = ['新媒体宣教Espresso1：人人宣教', '新媒体宣教Espresso2：朋友圈是最大的禾场'];
            $total = count($items);
            $index = now()->subDay()->format('z') % $total;
            $item = $items[$index];
            $titles = explode('：', $item);
            $mp3 = 'http://www.jtoday.org/wp-content/uploads/2022/08/mavmm0'.str_pad($index + 1, 2, '0', STR_PAD_LEFT).'.mp3';

            return ResourceResponse::music([
                'url' => $mp3,
                'title' => str_replace('Espresso', '课程', $titles[0]),
                'description' => $titles[1],
            ]);
        }

        return null;
    }
}
