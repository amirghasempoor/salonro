<?php

test('a protected expert route is a 401 even without an Accept header', function () {
    $this->get('/expert/profile/info', ['Accept' => '*/*'])
        ->assertUnauthorized()
        ->assertJsonStructure(['message']);
});

test('a protected user route is a 401 even without an Accept header', function () {
    $this->get('/user/profile/info', ['Accept' => '*/*'])->assertUnauthorized();
});

test('a missing record is a generic 404 that does not leak the model name', function () {
    $response = $this->getJson(route('user.home.hallDetails', 999999))->assertNotFound();

    expect($response->json('type'))->toBe('not_found')
        ->and($response->json('message'))->toBe(__('messages.not_found'))
        ->and($response->getContent())->not->toContain('App\\\\Models');
});

test('an unknown path is a JSON 404 even without an Accept header', function () {
    $this->get('/nope', ['Accept' => 'text/html'])
        ->assertNotFound()
        ->assertHeader('Content-Type', 'application/json');
});
