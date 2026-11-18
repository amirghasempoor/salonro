<?php

use App\Models\Expert;
use Laravel\Sanctum\Sanctum;

test('a non-numeric id is a 404, not a database error', function (string $uri) {
    Sanctum::actingAs(Expert::factory()->create(), ['*'], 'expert');

    $this->getJson($uri)->assertNotFound();
})->with([
    'staff list without hall' => '/expert/staff/list',
    'hall services without hall' => '/expert/halls/services',
    'hall find' => '/expert/halls/find',
    'letters as hall id' => '/expert/halls/abc',
    'letters as reservation hall' => '/expert/reservations/abc',
    'public hall details' => '/user/home/halls/abc',
]);
