<?php

use App\Resources\Handlers\LyAudio;
use Illuminate\Support\Facades\Config;

it('can retrieve all resources grouped by handler', function () {
    // Set required config for tests if necessary
    Config::set('services.youtube.api_key', 'test-key');

    $response = $this->get('/resources/all');

    $response->assertStatus(200);
    
    // Assert structure is keyed by handler names
    $response->assertJsonStructure([
        'BibleProject' => [
            '*' => ['keyword', 'title']
        ],
        'Febc' => [
            '*' => ['keyword', 'title']
        ],
        'LyAudio' => [
            '*' => ['keyword', 'title']
        ],
        'Ren' => [
            '*' => ['keyword', 'title']
        ],
        'Beta' => [
            '*' => ['keyword', 'title']
        ],
    ]);
    
    // Check for specific known resources within their handlers
    $response->assertJsonFragment(['keyword' => '783', 'title' => 'Bible Project']);
    
    // Verify specific handler content
    $bibleProject = $response->json('BibleProject');
    expect($bibleProject)->toContain(['keyword' => '783', 'title' => 'Bible Project']);

    $febc = $response->json('Febc');
    expect($febc)->toContain(['keyword' => '701', 'title' => '灵程真言']);
});

it('generates correct program list for LyAudio with preserved IDs', function () {
    $handler = new LyAudio();
    $response = $handler->resolve('600');
    
    expect($response->type)->toBe('text');
    $content = $response->data['content'];
    
    // Assert categories exist
    expect($content)->toContain('=====生活智慧=====');
    expect($content)->toContain('=====少儿家庭=====');
    
    // Assert IDs are preserved (not 0, 1, 2)
    expect($content)->toContain('【612】书香园地');
    expect($content)->not->toContain('【0】书香园地');
    
    expect($content)->toContain('【605】一起成长吧！');
    expect($content)->not->toContain('【0】一起成长吧！');
});
