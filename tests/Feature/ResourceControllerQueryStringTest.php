<?php

use App\Resources\Resources;

test('show route passes query string as part of keyword', function () {
    $mock = Mockery::mock(Resources::class);
    $mock->shouldReceive('resolve')
        ->once()
        ->with('https:/www.youtube.com/watch?v=TWRdbASb3To')
        ->andReturn(['type' => 'music', 'data' => ['url' => 'test']]);

    $this->app->instance(Resources::class, $mock);

    $response = $this->get('/resources/https:/www.youtube.com/watch?v=TWRdbASb3To');

    $response->assertStatus(200);
});
