<?php

namespace App\Resources\Handlers;

use App\Resources\Helpers\YouTubeHelper;
use App\Resources\ResourceResponse;
use Illuminate\Support\Facades\Http;

class PastorJiang
{
    public function resolve(string $keyword): ?ResourceResponse
    {
        if ($keyword == 900) {
            $who = '@jiangyongliu';
            $baseUrl = 'https://pub-3813a5d14cba4eaeb297a0dba302143c.r2.dev/youtube_channels/latest_update';

            return $this->fetchVideoData($baseUrl, $who, 'streams', $keyword);
        }

        if (preg_match('/^(90[1-9]|9[1-9][0-9])(\d{2})?$/', $keyword, $matches)) {
            $allPlayList = $this->getAllPlaylist();
            $who = '@jiangyongliu';

            $playlistNumber = intval($matches[1]);
            $playlistIndex = count($allPlayList) - ($playlistNumber - 900);

            if ($playlistIndex < 0 || $playlistIndex >= count($allPlayList)) {
                return null;
            }

            $playlist = $allPlayList[$playlistIndex];
            $playListId = $playlist['list'];
            $playlistTitle = $playlist['title'];

            if (! isset($matches[2])) {
                return ResourceResponse::text([
                    'title' => $playlistTitle,
                    'description' => "播放列表编号: {$playlistNumber}\n播放列表ID: {$playListId}\n@江涌流牧师的频道",
                ]);
            }

            try {
                $all = YouTubeHelper::getAllItemsByPlaylistId($playListId);
                $videoNumber = intval($matches[2]);
                $videoIndex = $all->count() - $videoNumber;

                if ($videoIndex < 0 || $videoIndex >= $all->count()) {
                    return null;
                }

                $item = $all->values()->get($videoIndex);
                $vid = $item->snippet->resourceId->videoId;
                $title = $item->snippet->title;
                $desc = "{$playlistTitle} @江涌流牧师的频道";

                $audioData = ResourceResponse::music([
                    'url' => config('x-resources.r2_share_audio')."/{$who}/{$vid}.m4a",
                    'title' => $title,
                    'description' => $desc,
                    'vid' => $vid,
                    'image' => $item->snippet->thumbnails->medium->url ?? '',
                ]);

                $videoData = ResourceResponse::link([
                    'url' => config('x-resources.r2_share_video')."/{$who}/{$vid}.mp4",
                    'title' => $title,
                    'description' => $desc,
                    'vid' => $vid,
                    'image' => $item->snippet->thumbnails->medium->url ?? '',
                ]);

                $audioData->addition = $videoData;

                return $audioData;
            } catch (\Exception $e) {
                return null;
            }
        }

        return null;
    }

    private function fetchVideoData($baseUrl, $who, $type, $keywordId): ?ResourceResponse
    {
        try {
            $url = "{$baseUrl}/{$who}_{$type}.json";
            $json = Http::get($url)->json();
            $vid = $json['id'];

            $videoData = $this->buildMediaData('link', $who, $vid, $json, $keywordId, 'video');
            $audioData = $this->buildMediaData('music', $who, $vid, $json, $keywordId, 'audio');

            $audioData->addition = $videoData;

            return $audioData;
        } catch (\Exception $e) {
            return null;
        }
    }

    private function buildMediaData($type, $who, $vid, $json, $keywordId, $statisticsType): ResourceResponse
    {
        $isVideo = ($type === 'link');
        $urlBase = $isVideo ? config('x-resources.r2_share_video') : config('x-resources.r2_share_audio');
        $extension = $isVideo ? 'mp4' : 'm4a';

        $data = [
            'url' => $urlBase."/{$who}/{$vid}.{$extension}",
            'title' => $json['title'],
            'description' => '江涌流牧师的频道',
            'vid' => $vid,
            'image' => $json['thumbnails'][3]['url'],
        ];

        $statistics = [
            'metric' => class_basename(__CLASS__),
            'keyword' => $keywordId,
            'type' => $statisticsType,
        ];

        return $isVideo ? ResourceResponse::link($data, $statistics) : ResourceResponse::music($data, $statistics);
    }

    private function getAllPlaylist(): array
    {
        return [
            ['list' => 'PLS3hpSkHQMMNXfIKfBQ-06rj0tbVyIpn6', 'title' => '202510全球医治大会'],
            ['list' => 'PLS3hpSkHQMMN0tic0nvNTNBHamIkKDdY2', 'title' => '圣灵充满祷告会'],
            ['list' => 'PLS3hpSkHQMMMhavcSPR6RJN9zXDe1iAQ', 'title' => '圣灵充满说方言'],
            ['list' => 'PLS3hpSkHQMMPPbAj0-Cf-sAV-XJBQL7oU', 'title' => '基督与律法的区别'],
            ['list' => 'PLS3hpSkHQMMM_jiWidwt8dculOsNO1i7I', 'title' => '属灵餐会系列'],
            ['list' => 'PLS3hpSkHQMMOS5ft7sE9W1_49ODq0h4pA', 'title' => '婚姻家庭保卫战'],
            ['list' => 'PLS3hpSkHQMMPRBD4HLkakS9G_DDBLn1_F', 'title' => '起点是复活'],
            ['list' => 'PLS3hpSkHQMMNns4ajD3Fuazyl85wuMksZ', 'title' => '身体灵化医治训练营'],
            ['list' => 'PLS3hpSkHQMMNHiT8mhQL3m-NVuG-pZDmp', 'title' => '认识亚伯拉罕的信心'],
            ['list' => 'PLS3hpSkHQMMOZxQLY3xicaikxN_INtjgc', 'title' => '全球医治大会'],
            ['list' => 'PLS3hpSkHQMMNUpDS-19O3b-fN0Ln4gTpx', 'title' => '活出王权'],
            ['list' => 'PLS3hpSkHQMMOnNiwoISW3J_2FgMCCE1wr', 'title' => '得胜新生活祷告会'],
            ['list' => 'PLS3hpSkHQMMOrbD9DRrAQsqPoxMWKjIg-', 'title' => '圣灵充满荣耀生活'],
            ['list' => 'PLS3hpSkHQMMODEDMNv5sf4IXGqmi7z3un', 'title' => '信心生活两大法则'],
            ['list' => 'PLS3hpSkHQMMOKtHG7d4OMLPWmEFc5UdNG', 'title' => '活出基督的信心'],
            ['list' => 'PLS3hpSkHQMMMn5A9cJL05EbSYxRHvEt1M', 'title' => '认识属天的信心系列课程'],
            ['list' => 'PLS3hpSkHQMMO67nbrAuucnRVVe52sWsUc', 'title' => '基督徒婚前辅导'],
            ['list' => 'PLS3hpSkHQMMPhJf7hXZ0fRgFlApFovVsE', 'title' => '精神类疾病祷告会'],
            ['list' => 'PLS3hpSkHQMMOhZF2rrEKvOxX24uaGGsLI', 'title' => '过大有能力的新生活'],
            ['list' => 'PLS3hpSkHQMMP4MlITZSzNkQ4PuGjw9N9m', 'title' => '肾脏疾病医治祷告法'],
            ['list' => 'PLS3hpSkHQMMO3kvglWqr2bG98gB5hI04i', 'title' => '医治祷告三部曲'],
            ['list' => 'PLS3hpSkHQMMMO0nFfLJCrNgHd4-fq5GDw', 'title' => '表演基督实践'],
            ['list' => 'PLS3hpSkHQMMN5s4gbVvYBfP2DwL7rPFXY', 'title' => '表演基督'],
            ['list' => 'PLS3hpSkHQMMOt4Nd0jSybhbnAGqAbc6cH', 'title' => '领受医治的爱'],
            ['list' => 'PLS3hpSkHQMMPimRqEy9xRf1o9hD0OKKgc', 'title' => '活出全新的爱系列'],
            ['list' => 'PLS3hpSkHQMMM1QoHA2AaP_BIhEYmfMp-9', 'title' => '2025『五一』神迹医治祷告会'],
            ['list' => 'PLS3hpSkHQMMN3Bc8WOzTWIwhmL4W0l4bw', 'title' => '认清教会中盛行的魔鬼谎言'],
            ['list' => 'PLS3hpSkHQMMOR4QgaD7nwdgd7RcPkWEGv', 'title' => '重大疾病灵化医治服侍专场'],
            ['list' => 'PLS3hpSkHQMMOeJ8_27D4iHdJfBIla87i1', 'title' => '灵化医治方法推进聚会'],
            ['list' => 'PLS3hpSkHQMMNWY9_u2fohNnpbnzLsskZ5', 'title' => '身体灵化医治实战训练'],
            ['list' => 'PLS3hpSkHQMMPGOt1QXYL5EquHx-ZkMgVb', 'title' => '身体灵化医治手册'],
            ['list' => 'PLS3hpSkHQMMNuPS2-CJJNA3WLfiuuEkdb', 'title' => '灵化医治方法发布会'],
            ['list' => 'PLS3hpSkHQMMMXVIq02HrvJVH7QNsk8fjn', 'title' => '领受复活大能力'],
            ['list' => 'PLS3hpSkHQMMNEwAsELlQOjmxlEk-5KBp9', 'title' => '神大能医治祷告会'],
            ['list' => 'PLS3hpSkHQMMOtEiAzVyMenAB3uIZ4JNFg', 'title' => '灵命成长系列（灵魂体）'],
            ['list' => 'PLS3hpSkHQMMNtcwVhwB6VQk9TeikUeLlm', 'title' => '建造强大兴盛的家'],
            ['list' => 'PLS3hpSkHQMMNpGcCHcfmHXTmUBiHpCDO5', 'title' => '身体的唯一出路'],
            ['list' => 'PLS3hpSkHQMMP44hKYA9N3x2_k_IjO0sB3', 'title' => '圣餐的大能'],
            ['list' => 'PLS3hpSkHQMMOm5A0ybLoOJSch1EEjcmku', 'title' => '实训课程：身体与圣灵合一'],
            ['list' => 'PLS3hpSkHQMMOCTYDjLz9IfwBQn3pNqmoh', 'title' => '身体内脏器官清洁祷告法实战课程'],
            ['list' => 'PLS3hpSkHQMMOTT3vARlLsZ6Km1c8k_0yf', 'title' => '特会专辑'],
            ['list' => 'PLS3hpSkHQMMM7aeJuFRbpHLVpVvgl7Awj', 'title' => '重塑一个新身体'],
            ['list' => 'PLS3hpSkHQMMPnOh-0rNn6wxuymDJVQprE', 'title' => '耶稣为我们作了什么？'],
            ['list' => 'PLS3hpSkHQMMPYWCFLNDis61tXFQwbgkJf', 'title' => '你得着了基督的身体'],
            ['list' => 'PLS3hpSkHQMMMehu0CfZ85tb_2N7OBPT8M', 'title' => '宣告出一个全新的自己'],
            ['list' => 'PLS3hpSkHQMMPfRS3jzhzcivn8MVwMReA_', 'title' => '活出话语的大能来'],
            ['list' => 'PLS3hpSkHQMMNiRozRzaHmBw8gSFITXvBi', 'title' => '真正的认罪与悔改'],
            ['list' => 'PLS3hpSkHQMMOziHaElK7UKnkeHj1siULI', 'title' => '最有能力的就是你'],
            ['list' => 'PLS3hpSkHQMMP3NVw8c2vqaRLIpVhMwTS1', 'title' => '十月疑难重症医治祷告会'],
            ['list' => 'PLS3hpSkHQMMNLcSx-wPmVJOMjbnlg-TEO', 'title' => '十一神迹大能祷告会'],
            ['list' => 'PLS3hpSkHQMMPJHJTWlr3F-0E1qYLQ3e_w', 'title' => '作耶稣基督所作的事'],
            ['list' => 'PLS3hpSkHQMMMfbYBg0ZfloQbiXE2oPDyN', 'title' => '活出复活大能的新样来'],
            ['list' => 'PLS3hpSkHQMMN3QBmGPKDwTWPElISvHUnD', 'title' => '圣餐：无可限量的祝福'],
            ['list' => 'PLS3hpSkHQMMO9HrvwgqjVcfsCqpvWmtXE', 'title' => '全然健康祷告会'],
            ['list' => 'PLS3hpSkHQMMPZ_nR6qJMwtP_B3IYrRWZy', 'title' => '因信称义系列'],
            ['list' => 'PLS3hpSkHQMMPFnyJYm2ndnIObpsYTzbVb', 'title' => '你里面的圣灵'],
            ['list' => 'PLS3hpSkHQMMPXM02wIrsIVcRlNFsP4xIi', 'title' => '身体被复活的大能掌管'],
            ['list' => 'PLS3hpSkHQMMMujp7Oq2QR1HM3jJjuLp9B', 'title' => '身体灵化强化训练'],
            ['list' => 'PLS3hpSkHQMMOZAO2wxFxvpoFes43IwNW2', 'title' => '活出身体的全新样式'],
            ['list' => 'PLS3hpSkHQMMP1IUYSv0B0Y03iA8HRVFvp', 'title' => '疑难重大疾病服侍专场'],
            ['list' => 'PLS3hpSkHQMMPEGDSIGJn92O-wUWUyO1l1', 'title' => '断开贫穷的轭'],
            ['list' => 'PLS3hpSkHQMMMLU6VgLuaplpFOuTlZJIRy', 'title' => '活出统管财富的命定'],
            ['list' => 'PLS3hpSkHQMMNYbMJjb_7_oR27r71FIgVQ', 'title' => '重大疾病祷告会'],
            ['list' => 'PLS3hpSkHQMMMoGQyr9c0ewk9huJ44n5p0', 'title' => '神与人同在系列课程'],
            ['list' => 'PLS3hpSkHQMMPTGqVQqXY5bItl3jQwTR4V', 'title' => '有效祷告的秘密法则'],
            ['list' => 'PLS3hpSkHQMMNsF4bjFQSUmGCpxn9XjiIi', 'title' => '心脏疾病医治洁净祷告法'],
            ['list' => 'PLS3hpSkHQMMMqvICnhQPXEua5ZEQiaF_F', 'title' => '让家重新得力'],
            ['list' => 'PLS3hpSkHQMMMl8S87kVw8zdAIMnfomP5z', 'title' => '真理成为肉身显现出来'],
            ['list' => 'PLS3hpSkHQMMP2ol8qBSwww5syyC4JLMMf', 'title' => '乳腺肿瘤医治祷告会'],
            ['list' => 'PLS3hpSkHQMMOCfByyFsmRAsXX1As8npDi', 'title' => '基督里的真富足'],
            ['list' => 'PLS3hpSkHQMMNsFZJSm4vP_X8siCW0Os8w', 'title' => '婚姻家庭真理分享与辅导'],
            ['list' => 'PLS3hpSkHQMMN7pBL02xVf9XDtbqS2uGV_', 'title' => '基督里的真健康'],
            ['list' => 'PLS3hpSkHQMMNE1nZDXOd5r_m5akYZO7kp', 'title' => '基督徒蒙福的关键'],
            ['list' => 'PLS3hpSkHQMMMDdl6ZaeZW1EvSOQkEUlcN', 'title' => '抑郁，离开！全球祷告会'],
            ['list' => 'PLS3hpSkHQMMPykN_xMKc-hADnYjDRs8Rn', 'title' => '基督复活的奥秘'],
            ['list' => 'PLS3hpSkHQMMMhiBiCKkXsWFKBJx9x2QBr', 'title' => '跟着耶稣学医治'],
            ['list' => 'PLS3hpSkHQMMPvBlSlL8CRAco5G66lduuG', 'title' => '无坚不摧的肿瘤消失术'],
            ['list' => 'PLS3hpSkHQMMOZrTga6lIjMKKiTUJoV4-s', 'title' => '永恒的国度全新的生活'],
            ['list' => 'PLS3hpSkHQMMNqQgv9teTex5aCLZ0fGsp9', 'title' => '神迹大能医治祷告会'],
            ['list' => 'PLS3hpSkHQMMNopVhQBcFQNXrJgGcWPnVh', 'title' => '头部医治洁净祷告特会'],
            ['list' => 'PLS3hpSkHQMMMRBImcSeLIAkGjhf4gQWIk', 'title' => '牧师带你来灵修'],
            ['list' => 'PLS3hpSkHQMMNkVX_IfIXbMwcCbswQnkq7', 'title' => '恢复视力祷告会'],
            ['list' => 'PLS3hpSkHQMMM5MUVrSIsofK6QptY08M-S', 'title' => '特会神圣的膏抹江涌流牧师'],
            ['list' => 'PLS3hpSkHQMMO4a_bSVyNmU6qjHmpuLZ_b', 'title' => '特会领受从天降下的火系列'],
            ['list' => 'PLS3hpSkHQMMPbvPvmCHDo8dujQqC9QaR0', 'title' => '身体更新医治营会'],
            ['list' => 'PLS3hpSkHQMMOAuSgoo1hRu0sgFHDYiy6I', 'title' => '短视频'],
            ['list' => 'PLS3hpSkHQMMMYL8ncijUgZ3UI5WKs65sY', 'title' => '新生命宣言'],
            ['list' => 'PLS3hpSkHQMMOa6XrN5K7qImifePUhd2Vs', 'title' => '新生命真理'],
            ['list' => 'PLS3hpSkHQMMNM4m_HlFhPzg62VBtzvngu', 'title' => '跨时空医治祷告会'],
            ['list' => 'PLS3hpSkHQMMPOxbNC_uCvxgPc0gEWIDss', 'title' => '抑郁症离开专题'],
            ['list' => 'PLS3hpSkHQMMPl22p0Q85tCRX_qsInBbuk', 'title' => '真理杂谈'],
            ['list' => 'PLS3hpSkHQMMMfhJNmmoemmTaG0ZVjx0JY', 'title' => '主日信息'],
            ['list' => 'PLS3hpSkHQMMMmPySGo6zqUwxzaC2VaNJE', 'title' => '真理问答'],
            ['list' => 'PLS3hpSkHQMMOdpLecH9MBIvilV6_aZCnv', 'title' => '主题课程'],
        ];
    }
}
