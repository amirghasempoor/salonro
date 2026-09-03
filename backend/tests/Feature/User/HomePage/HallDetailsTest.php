<?php

use App\Models\Hall;

test('the hall details endpoint is public and requires no authentication', function () {
    $hall = Hall::factory()->create();

    $this->getJson(route('user.home.hallDetails', $hall))->assertOk();
});

test('it returns the hall details', function () {
    $hall = Hall::factory()->create([
        'name' => 'Alpha Salon',
        'address' => 'Valiasr St',
        'telephone' => '02112345678',
    ]);

    $response = $this->getJson(route('user.home.hallDetails', $hall));

    $response->assertOk();
    $response->assertJsonStructure([
        'data' => [
            'id', 'name', 'owner_id', 'owner_name', 'lat', 'lng', 'address',
            'postal_code', 'telephone', 'province_id', 'province_name',
            'city_id', 'city_name', 'is_active', 'description',
            'created_at', 'updated_at',
        ],
    ]);
    $response->assertJsonPath('data.id', $hall->id);
    $response->assertJsonPath('data.name', 'Alpha Salon');
    $response->assertJsonPath('data.address', 'Valiasr St');
    $response->assertJsonPath('data.telephone', '02112345678');
});

test('it returns 404 for a hall that does not exist', function () {
    $this->getJson(route('user.home.hallDetails', 999))->assertNotFound();
});
