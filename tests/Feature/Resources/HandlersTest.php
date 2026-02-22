<?php

use App\Resources\Resources;
use App\Resources\ResourceResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;

it('can resolve keywords from all handlers', function (string $keyword, string $expectedType) {
    // Set required config for tests
    Config::set('services.youtube.api_key', 'test-key');
    
    // Mock external HTTP requests
    Http::fake([
        'bibleproject.com/*' => Http::response('<html><body><div class="intl-downloads-item-title">Test</div><a href="test.mp4"></a></body></html>'),
        'febc.blob.core.windows.net/*' => Http::response([['path' => 'test.mp3', 'time' => '260220', 'title' => 'Test']]),
        'docs.google.com/*' => Http::response('<tr><td>'.now()->format('n-j-Y').'</td><td>c</td><td>Desc</td><td>Text</td></tr>'),
        'www.tpehoc.org.tw/*' => Http::response('<html><body><h3 class="post-content-outer"><a href="link">Title</a></h3><div class="post-content">Description</div><source src="test.mp3"></body></html>'),
        'www.zanmei.ai/*' => Http::response('<html><body><a href="/song/123.html">Title</a></body></html>'),
        'x.lydt.work/*' => Http::response(['data' => [['link' => 'test.mp3', 'alias' => 'fa260220', 'program' => ['name' => 'Test'], 'description' => 'Desc']]]),
        'pub-3813a5d14cba4eaeb297a0dba302143c.r2.dev/youtube_channels/*' => Http::response(['id' => 'vid', 'title' => 'Title', 'thumbnails' => [3 => ['url' => 'img']]]),
        'pub-3813a5d14cba4eaeb297a0dba302143c.r2.dev/hland/*' => Http::response(array_fill(0, 100, ['title' => 'Title', 'url' => 'http://example.com/blog/123'])),
        'pub-6de883f3fd4a43c28675e9be668042c2.r2.dev/*' => Http::response(['list' => [['title' => 'Test', 'video_url' => 'test.mp3']], 'details' => [['author' => 'Author', 'img_url' => 'img', 'title' => 'Title']]]),
    ]);

    // Manually register Youtube binding for Ren handler if needed, but since it's a helper call it should be fine if mocked or configured.
    // However, Ren.php uses YouTubeHelper which uses Madcoda\Youtube facade.
    // For unit testing we might need to mock the Helper or the Facade.
    // But Ren.php has a check: if (! config('services.youtube.api_key')) { return text; }
    // By setting the config above, it should proceed to call the Helper.
    // Let's mock the Helper or just allow it if we set the key.
    
    // Actually, YouTubeHelper::getAllItemsByPlaylistId will call the Youtube facade.
    // We should mock the facade result.
    \Madcoda\Youtube\Facades\Youtube::shouldReceive('getPlaylistItemsByPlaylistIdAdvanced')
        ->andReturn(['results' => [(object)['snippet' => (object)['title' => 'Title', 'resourceId' => (object)['videoId' => 'vid'], 'description' => 'Desc']]], 'info' => []]);

    $resources = new Resources();
    $result = $resources->resolve($keyword);

    if ($keyword === '7830') {
        expect($result)->toBeArray();
    } else {
        expect($result)->toBeInstanceOf(ResourceResponse::class);
        expect($result->type)->toBe($expectedType);
    }
})->with([
    ['783', 'link'],
    ['7830', 'array'],
    ['701', 'music'],
    ['702', 'music'],
    ['703', 'music'],
    ['704', 'music'],
    ['705', 'music'],
    ['706', 'music'],
    ['707', 'music'],
    ['708', 'music'],
    ['709', 'music'],
    ['710', 'music'],
    ['711', 'music'],
    ['712', 'music'],
    ['713', 'music'],
    ['714', 'music'],
    // LyAudio Programs
    ['601', 'music'],
    ['602', 'music'],
    ['603', 'music'],
    ['604', 'music'],
    ['605', 'music'],
    ['606', 'music'],
    ['607', 'music'],
    ['608', 'music'],
    ['610', 'music'],
    ['611', 'music'],
    ['612', 'music'],
    ['613', 'music'],
    ['614', 'music'],
    ['616', 'music'],
    ['617', 'music'],
    ['618', 'music'],
    ['619', 'music'],
    ['620', 'music'],
    ['657', 'music'],
    ['659', 'music'],
    ['660', 'music'],
    ['664', 'music'],
    ['668', 'music'],
    ['674', 'music'],
    ['675', 'music'],
    ['678', 'music'],
    ['680', 'music'],
    ['698', 'music'],
    ['639', 'music'],
    ['646', 'music'],
    ['624', 'music'],
    ['630', 'music'],
    ['640', 'music'],
    ['628', 'music'],
    ['652', 'music'],
    ['672', 'music'],
    ['609', 'music'],
    ['626', 'music'],
    ['621', 'music'],
    ['622', 'music'],
    ['676', 'music'],
    ['654', 'music'],
    ['681', 'music'],
    ['629', 'music'],
    ['615', 'music'],
    ['625', 'music'],
    ['648', 'music'],
    ['641', 'music'],
    ['642', 'music'],
    ['643', 'music'],
    ['644', 'music'],
    ['645', 'music'],
    ['671', 'music'],
    ['650', 'music'],
    ['651', 'music'],
    ['649', 'music'],
    ['637', 'music'],
    ['653', 'music'],
    // Ren Programs
    ['813', 'link'],
    ['815', 'link'],
    ['816', 'link'],
    ['817', 'link'],
    ['818', 'link'],
    ['819', 'link'],
    ['820', 'link'],
    ['821', 'link'],
    ['822', 'link'],
    ['823', 'link'],
    ['824', 'link'],
    ['830', 'link'],
    ['831', 'link'],
    ['832', 'music'],
    // Tpehoc Programs
    ['798', 'music'],
    ['797', 'music'],
    ['795', 'music'],
    ['794', 'music'],
    ['793', 'music'],
    ['792', 'music'],
    ['785', 'music'],
    ['781', 'music'],
    ['789', 'text'],
    ['803', 'link'],
    ['hl46436', 'music'],
    ['mbc', 'link'],
    ['odb', 'music'],
    ['900', 'music'],
    ['801', 'music'],
    ['t001', 'music'],
    ['799', 'music'],
    ['赞美恩典', 'music'],
]);
