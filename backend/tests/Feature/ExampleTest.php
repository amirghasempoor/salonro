<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns a successful response for a public endpoint', function () {
    $response = $this->get('/api/provinces/list');

    $response->assertStatus(200);
});
