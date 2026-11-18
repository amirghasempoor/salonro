<?php

test('a listed origin is allowed', function () {
    $this->withHeaders(['Origin' => 'https://user.salonro.ir', 'Accept' => 'application/json'])
        ->getJson('/up')
        ->assertHeader('Access-Control-Allow-Origin', 'https://user.salonro.ir');
});

test('an unlisted origin is not reflected', function () {
    $response = $this->withHeaders(['Origin' => 'https://evil.example', 'Accept' => 'application/json'])
        ->getJson('/up');

    expect($response->headers->get('Access-Control-Allow-Origin'))->not->toBe('https://evil.example');
});
