<?php

test('a protected expert route is a 401 even without an Accept header', function () {
    $this->get('/expert/profile/info', ['Accept' => '*/*'])
        ->assertUnauthorized()
        ->assertJsonStructure(['message']);
});

test('a protected user route is a 401 even without an Accept header', function () {
    $this->get('/user/profile/info', ['Accept' => '*/*'])->assertUnauthorized();
});

test('an unknown path is a JSON 404 even without an Accept header', function () {
    $this->get('/nope', ['Accept' => 'text/html'])
        ->assertNotFound()
        ->assertHeader('Content-Type', 'application/json');
});
