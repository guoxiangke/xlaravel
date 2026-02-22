<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use App\Jobs\InfluxQueue;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// Resources API route
Route::get('/resources/all', [App\Http\Controllers\ResourceController::class , 'index'])
    ->name('resources.index');

Route::get('/resources/all/{handler}', [App\Http\Controllers\ResourceController::class , 'handlerIndex'])
    ->name('resources.handler_index');

Route::get('/resources/{keyword}', [App\Http\Controllers\ResourceController::class , 'show'])
    ->where('keyword', '.*')
    ->name('resources.show');

// 防失联2重备案域名跳转链接 go.url/s=share
// 127.0.0.1:8000/s?url=https://google.com/404?query=s&tag=test
// 127.0.0.1:8000/s?url=https://google.com/404?query=s%26tag=test
// https://go2024.simai.life/s?url=https://r2.check-in-out.online/OVagt1.JPG
// https://go.check-in-out.online/s?url=https://r2.check-in-out.online/OVagt1.JPG
Route::get('/s', function (Request $request) {
    $url = $request->query('url');
    $status = 302;
    $headers = ['referer' => $url];

    // TODO: 统计数据 GA or influxdb！ or Redis counts
    // $ip = $request->header('x-forwarded-for')??$request->ip();
    // XstatisticsLinkQueue::dispatchAfterResponse($ip, $url, $data);

    // table:
    // IP: 127.0.0.1 url: https://go.url.xxx count=1
    return redirect()->away($url, $status, $headers);
});

Route::get('/redirect', function (Request $request) {
    // dd($request->query());

    // http://127.0.0.1:8000/redirect?target=https://*.com/@fwdforward/7XFVL5o.m4a?metric=connect%26category=601%26bot=4
    // metric:默认是connect 收听/看/点击链接
    // by：author 可选 %26author=@fwdforward
    // http://127.0.0.1:8000/redirect?target=https://navs.savefamily.net%3Fvips=10,15,11,16,17%26metric=NFC%26keyword=nav

    // %3F ?
    // %26 &
    $url = $request->query('target');
    $status = 302;
    $ip = $request->header('x-forwarded-for') ?? $request->ip();
    $parts = parse_url($url); //$parts['host']
    // $paths = pathinfo($url); //mp3
    $parsedUrl = parse_url($url);
    $headers = ['referer' => $parsedUrl['scheme'] . '://' . $parsedUrl['host']]; //strtok($url, '?')//remove ?q=xxx

    $target = basename($url); //cc201221.mp3

    $tags = [];
    if (isset($parts['query']))
        parse_str($parts['query'], $tags);
    $tags['host'] = $parts['host'];
    // measurement/metric
    // $tags = http_build_query($data, '', ',');// category=603,bot=4    

    $fields = [];
    $fields['count'] = 1;
    $fields['target'] = strtok($target, '?');
    $fields['ip'] = $ip;
    // $fields = http_build_query($fields, '', ',');// category=603,bot=4

    // 原始获取人！
    // $url .= '%26to='.$to; //unset(to) => Field[to]=wxid;
    if (isset($tags['to'])) {
        $fields['to'] = $tags['to'];
        unset($tags['to']);
    }
    // ?_=1
    if (isset($tags['_']))
        unset($tags['_']);

    $protocolLine = [
        'name' => 'click', //action=click/listen/view/tap
        'tags' => $tags,
        'fields' => $fields
    ];
    // $protocolLine = $metric.$tags.' count=1i,target="'.$target.'",ip="'.$ip.'"';
    // ly-listen,category=603,bot=%E5%8F%8B4count=1i,target="ee230909.mp3"
    // dd($protocolLine,$parts,$url);
    InfluxQueue::dispatchAfterResponse($protocolLine);
    return redirect()->away($url, $status, $headers);
});

Route::get('/go/pastorlu', function (Request $request) {
    $res = Http::get("https://x-resources.vercel.app/resources/801")->json();
    return redirect()->away($res['data']['url'], $status = 302);
});

require __DIR__ . '/settings.php';