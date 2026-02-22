<?php

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
        'Tpehoc' => [
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
