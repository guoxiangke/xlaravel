<?php

use App\Resources\Helpers\ImageHelper;
use Illuminate\Support\Facades\Config;

beforeEach(function () {
    Config::set('x-resources.thumbnail_proxy', 'https://wsrv.nl');
    Config::set('x-resources.thumbnail_width', 400);
});

it('wraps an image url into a jpeg thumbnail proxy url', function () {
    expect(ImageHelper::thumbnail('https://images.simai.life/images/2026/09/abc.jpg'))
        ->toBe('https://wsrv.nl/?url=images.simai.life%2Fimages%2F2026%2F09%2Fabc.jpg&w=400&output=jpg&bg=white');
});

it('strips both http and https schemes from the source url', function () {
    expect(ImageHelper::thumbnail('http://example.com/a.png'))
        ->toBe('https://wsrv.nl/?url=example.com%2Fa.png&w=400&output=jpg&bg=white');
});

it('encodes query strings so they are not swallowed by the proxy url', function () {
    $result = ImageHelper::thumbnail('https://i.ytimg.com/vi/abc/hq720.jpg?sqp=-oay&rs=AOn4');

    expect($result)->toBe('https://wsrv.nl/?url=i.ytimg.com%2Fvi%2Fabc%2Fhq720.jpg%3Fsqp%3D-oay%26rs%3DAOn4&w=400&output=jpg&bg=white');
    expect(substr_count($result, '&w='))->toBe(1);
});

it('honors an explicit width', function () {
    expect(ImageHelper::thumbnail('https://example.com/a.jpg', 200))
        ->toContain('&w=200&');
});

it('returns the original url when the proxy is disabled', function () {
    Config::set('x-resources.thumbnail_proxy', '');

    expect(ImageHelper::thumbnail('https://example.com/a.jpg'))
        ->toBe('https://example.com/a.jpg');
});

it('returns an empty string for an empty url', function () {
    expect(ImageHelper::thumbnail(''))->toBe('');
});

it('builds a youtube thumbnail from hqdefault rather than maxresdefault', function () {
    $result = ImageHelper::youtubeThumbnail('vidABC');

    expect($result)->toContain('i.ytimg.com%2Fvi%2FvidABC%2Fhqdefault.jpg');
    expect($result)->not->toContain('maxresdefault');
    expect($result)->toContain('output=jpg');
});

it('does not wrap an already proxied url twice', function () {
    $once = ImageHelper::thumbnail('https://images.simai.life/a.jpg');

    expect(ImageHelper::thumbnail($once))->toBe($once);
});

it('normalizes a hand written proxy url by adding the missing parameters', function () {
    expect(ImageHelper::thumbnail('https://wsrv.nl/?url=media.h.land/prod/cover.jpg'))
        ->toBe('https://wsrv.nl/?url=media.h.land%2Fprod%2Fcover.jpg&w=400&output=jpg&bg=white');
});

it('applies the thumbnail proxy to every response image automatically', function () {
    $response = App\Resources\ResourceResponse::music([
        'url' => 'https://example.com/a.m4a',
        'title' => '标题',
        'image' => 'https://example.com/cover.png',
    ]);

    expect($response->data['image'])
        ->toBe('https://wsrv.nl/?url=example.com%2Fcover.png&w=400&output=jpg&bg=white');
});

it('leaves a response without an image untouched', function () {
    $response = App\Resources\ResourceResponse::music([
        'url' => 'https://example.com/a.m4a',
        'title' => '标题',
    ]);

    expect($response->data)->not->toHaveKey('image');
});

it('proxies the formerly hand written cover urls from their original source', function (string $source) {
    expect(ImageHelper::thumbnail($source))
        ->toStartWith('https://wsrv.nl/?url=')
        ->toEndWith('&w=400&output=jpg&bg=white')
        ->not->toContain('wsrv.nl%2F');
})->with([
    'https://images.simai.life/images/2026/09/96c607f27642e32e5bb81f712ad9337b.jpg',
    'https://images.simai.life/images/2026/09/954928fead3092a7e1e91ce59b96aa25.png',
    'https://media.h.land/prod/20220802-081010.828-small.jpg',
]);

it('does not route images through another image proxy', function () {
    // wsrv.nl 以 "Domain or TLD blocked by policy" 拒绝代理 wp.com 一类的图片代理，
    // 封面必须给出源站地址。
    expect(file_get_contents(app_path('Resources/Handlers/Beta.php')))
        ->not->toContain('i0.wp.com');
});

it('uses hqdefault for the 恩典365 youtube cover instead of mqdefault', function () {
    expect(file_get_contents(app_path('Resources/Handlers/Beta.php')))
        ->not->toContain('mqdefault');

    expect(ImageHelper::youtubeThumbnail('JCNu1COWfJY'))
        ->toContain('i.ytimg.com%2Fvi%2FJCNu1COWfJY%2Fhqdefault.jpg');
});
