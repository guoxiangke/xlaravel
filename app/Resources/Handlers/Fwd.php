<?php

namespace App\Resources\Handlers;

use App\Resources\ResourceResponse;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class Fwd
{
    public function getResourceList(): array
    {
        return [
            ['keyword' => '789', 'title' => '每日灵修分享'],
            ['keyword' => '803', 'title' => '主日崇拜'],
            ['keyword' => '804', 'title' => '祷告会'],
            ['keyword' => '806', 'title' => '主日信息'],
        ];
    }

    public function resolve(string $keyword): ?ResourceResponse
    {
        return match ($keyword) {
            '789' => $this->getDailyAudio(),
            '803' => $this->getSundayService(),
            '804' => $this->getPrayerMeeting(),
            '806' => $this->getSundayMessage(),
            default => null,
        };
    }

    private function getDailyAudio(): ResourceResponse
    {
        $whichDay = now();
        $year = $whichDay->format('Y');
        $date = $whichDay->format('ymd');

        // 正常每天有3个音频，周六日只有c音频
        $domain = config('x-resources.r2_share_audio').'/fwd';
        $mp3a = "{$domain}/".$year."/fwd{$date}_a.mp3";
        $mp3b = "{$domain}/".$year."/fwd{$date}_b.mp3";
        $mp3c = "{$domain}/".$year."/fwd{$date}_c.mp3";
        $image = 'https://is1-ssl.mzstatic.com/image/thumb/Podcasts116/v4/07/27/f8/0727f8f6-31db-b202-ffdf-209186d60c98/mza_14432271839699962252.jpg/600x600bb.webp';

        try {
            $client = new Client;
            $url = 'https://docs.google.com/spreadsheets/u/0/d/1xIdXT4mTKHRulwJeHkzL_1dUuSsirnriGNHMvlOfdCc/htmlview/sheet?headers=true&gid=0';
            $response = $client->get($url);
            $html = (string) $response->getBody();
            
            // 使用正则解析以避免本地库环境导致的 preg_match 错误
            preg_match_all('/<tr[^>]*>(.*?)<\/tr>/s', $html, $rows);
            $meta = [];
            
            foreach ($rows[1] as $rowHtml) {
                if (preg_match_all('/<td[^>]*>(.*?)<\/td>/s', $rowHtml, $tds)) {
                    if (count($tds[1]) >= 3) {
                        $cloumn1 = trim(strip_tags($tds[1][0])); // date
                        $cloumn2 = trim(strip_tags($tds[1][1])); // abc
                        $cloumn3 = trim(strip_tags($tds[1][2])); // desc
                        
                        $meta[$cloumn1 . $cloumn2] = $cloumn3;
                        if ($cloumn2 == 'c' && isset($tds[1][3])) {
                            $meta[$cloumn1 . $cloumn2 . '.text'] = trim(strip_tags($tds[1][3]));
                        }
                    }
                }
            }

            $dayStr = $whichDay->format('n-j-Y');
            $descA = $meta[$dayStr.'a'] ?? '';
            $descB = $meta[$dayStr.'b'] ?? '';
            $descC = $meta[$dayStr.'c'] ?? '';
            $textDescD = $meta[$dayStr.'c.text'] ?? "=灵修分享=\n今天的天英讨论问题是：";

            $additionC = ResourceResponse::music([
                'url' => $mp3c,
                'title' => '分享-'.$date,
                'description' => $descC,
                'image' => $image,
            ], [
                'metric' => 'Fwd',
                'keyword' => '789',
                'type' => 'audio',
            ]);

            // 根据原逻辑：周六日只发c，或者默认为 additionc
            // 原代码：'addition' => $additionc
            return ResourceResponse::text([
                'content' => $textDescD,
            ], [
                'metric' => 'Fwd',
                'keyword' => '789',
                'type' => 'text',
            ], $additionC);

        } catch (\Exception $e) {
            return ResourceResponse::text(['content' => '获取数据失败，请稍后重试'], [
                'metric' => 'Fwd',
                'keyword' => '789',
                'type' => 'error',
            ]);
        }
    }

    private function getSundayService(): ?ResourceResponse
    {
        try {
            $cacheKey = 'fwd_sunday_service_streams';
            $tomorrow = now('Asia/Shanghai')->addDay()->startOfDay();
            $secondsUntilTomorrow = $tomorrow->diffInSeconds(now('Asia/Shanghai'));
            
            $html = Cache::remember($cacheKey, $secondsUntilTomorrow, function () {
                return Http::get('https://www.youtube.com/@fwdforwardchurch7991/streams')->body();
            });

            $re = '/"text":"FWDFC ([^"]+).*?"videoId":"([^"]+)"/';
            preg_match_all($re, $html, $matches);

            foreach ($matches[1] as $key => $value) {
                if (Str::containsAll($value, ['主日崇拜', '日出神話'])) {
                    $title = $value;
                    $vid = $matches[2][$key];
                    break;
                }
            }

            if (! isset($vid)) {
                return null;
            }

            $channelDomain = config('x-resources.r2_share_audio').'/@fwdforwardchurch7991/';
            $url = $channelDomain.$vid.'.mp4';
            $image = config('x-resources.r2_share_audio').'/uPic/2023/IeDDmx.jpg';

            $descs = explode('【', $title);

            $videoResponse = ResourceResponse::link([
                'url' => $url,
                'title' => '【日出神話】主日崇拜線上直播',
                'description' => $descs[0],
                'image' => $image,
                'vid' => $vid,
            ], [
                'metric' => 'Fwd',
                'keyword' => '803',
                'type' => 'video',
            ]);

            // Add audio version
            $m4a = $channelDomain.$vid.'.m4a';
            $audioResponse = ResourceResponse::music([
                'url' => $m4a,
                'title' => '【日出神話】主日崇拜線上直播',
                'description' => $descs[0],
                'image' => $image,
                'vid' => $vid,
            ], [
                'metric' => 'Fwd',
                'keyword' => '803',
                'type' => 'audio',
            ]);

            $videoResponse->addition = $audioResponse;

            return $videoResponse;

        } catch (\Exception $e) {
            return null;
        }
    }

    private function getPrayerMeeting(): ?ResourceResponse
    {
        try {
            $cacheKey = 'fwd_prayer_meeting_streams';
            $tomorrow = now('Asia/Shanghai')->addDay()->startOfDay();
            $secondsUntilTomorrow = $tomorrow->diffInSeconds(now('Asia/Shanghai'));
            
            $html = Cache::remember($cacheKey, $secondsUntilTomorrow, function () {
                return Http::get('https://www.youtube.com/@fwdforwardchurch7991/streams')->body();
            });

            $re = '/"text":"FWDFC ([^"]+).*?"videoId":"([^"]+)"/';
            preg_match_all($re, $html, $matches);

            foreach ($matches[1] as $key => $value) {
                if (Str::contains($value, '禱告會')) {
                    $title = $value;
                    $vid = $matches[2][$key];
                    break;
                }
            }

            if (! isset($vid)) {
                return null;
            }

            $channelDomain = config('x-resources.r2_share_audio').'/@fwdforwardchurch7991/';
            $url = $channelDomain.$vid.'.mp4';
            $image = config('x-resources.r2_share_audio').'/uPic/2023/IeDDmx.jpg';

            $videoResponse = ResourceResponse::link([
                'url' => $url,
                'title' => '前進教會週三禱告會',
                'description' => $title,
                'image' => $image,
                'vid' => $vid,
            ], [
                'metric' => 'Fwd',
                'keyword' => '804',
                'type' => 'video',
            ]);

            // Add audio version
            $m4a = $channelDomain.$vid.'.m4a';
            $audioResponse = ResourceResponse::music([
                'url' => $m4a,
                'title' => '前進教會週三禱告會',
                'description' => $title,
                'image' => $image,
                'vid' => $vid,
            ], [
                'metric' => 'Fwd',
                'keyword' => '804',
                'type' => 'audio',
            ]);

            $videoResponse->addition = $audioResponse;

            return $videoResponse;

        } catch (\Exception $e) {
            return null;
        }
    }

    private function getSundayMessage(): ?ResourceResponse
    {
        $isSetOn = Cache::get('806', false);
        if (! $isSetOn) {
            return null;
        }

        try {
            $cacheKey = 'fwd_sunday_message_playlist';
            $tomorrow = now('Asia/Shanghai')->addDay()->startOfDay();
            $secondsUntilTomorrow = $tomorrow->diffInSeconds(now('Asia/Shanghai'));
            
            $matches = Cache::remember($cacheKey, $secondsUntilTomorrow, function () {
                $response = Http::get('https://x-resources.vercel.app/youtube/get-last-by-playlist/PLLDxN82mMW3NrAoY-Nm6JYsk6ib5_5AZf');
                if ($response->failed()) {
                    return null;
                }
                return $response->json();
            });
            
            if (!$matches) {
                return null;
            }
            $vid = $matches['contentDetails']['videoId'];
            $title = $matches['snippet']['title'];
            $channelDomain = config('x-resources.r2_share_audio').'/@fwdforwardchurch7991/';
            $url = $channelDomain.$vid.'.mp4';
            $image = config('x-resources.r2_share_audio').'/uPic/2023/IeDDmx.jpg';

            $titleArray = explode('｜', $title);
            $mainTitle = $titleArray[0];
            array_shift($titleArray);
            $description = implode('｜', $titleArray);

            $videoResponse = ResourceResponse::link([
                'url' => $url,
                'title' => $mainTitle,
                'description' => $description,
                'image' => $image,
                'vid' => $vid,
            ], [
                'metric' => 'Fwd',
                'keyword' => '806',
                'type' => 'video',
            ]);

            // Add audio version
            $m4a = $channelDomain.$vid.'.m4a';
            $audioResponse = ResourceResponse::music([
                'url' => $m4a,
                'title' => $mainTitle,
                'description' => $description,
                'image' => $image,
                'vid' => $vid,
            ], [
                'metric' => 'Fwd',
                'keyword' => '806',
                'type' => 'audio',
            ]);

            $videoResponse->addition = $audioResponse;

            return $videoResponse;
        } catch (\Exception $e) {
            return null;
        }
    }
}
