<?php

use App\Models\Hall;

test('the nearby halls endpoint is public and requires no authentication', function () {
    $this->getJson(route('user.home.hallsInArea', [
        'lat' => 35.7, 'lng' => 51.4,
        'filters' => json_encode([]),
        'sorting' => json_encode([]),
    ]))->assertOk();
});

test('it validates the coordinates', function () {
    $this->getJson(route('user.home.hallsInArea'))
        ->assertStatus(422)
        ->assertJsonValidationErrors(['lat', 'lng']);
});

test('it returns only active halls within 10km, nearest first', function () {
    // User is at (35.70, 51.40).
    $close = Hall::factory()->create(['lat' => '35.70', 'lng' => '51.40']);   // ~0 km
    $near = Hall::factory()->create(['lat' => '35.75', 'lng' => '51.40']);    // ~5.6 km
    Hall::factory()->create(['lat' => '36.00', 'lng' => '51.40']);           // ~33 km, excluded
    Hall::factory()->create(['lat' => '35.70', 'lng' => '51.40', 'is_active' => false]); // close but inactive

    $response = $this->getJson(route('user.home.hallsInArea', [
        'lat' => 35.70, 'lng' => 51.40,
        'filters' => json_encode([]),
        'sorting' => json_encode([]),
    ]));

    $response->assertOk();
    $response->assertJsonCount(2, 'data');
    $response->assertJsonPath('data.0.id', $close->id);
    $response->assertJsonPath('data.1.id', $near->id);
    $response->assertJsonStructure([
        'data' => [
            ['id', 'name', 'address', 'province_name', 'city_name', 'telephone', 'lat', 'lng', 'distance'],
        ],
        'meta' => ['totalRowCount'],
    ]);

    $data = $response->json('data');
    expect((float) $data[0]['distance'])->toBe(0.0);
    expect((float) $data[1]['distance'])->toBeGreaterThan(0.0)->toBeLessThanOrEqual(10.0);
});

test('it returns an empty list when no hall is within range', function () {
    Hall::factory()->create(['lat' => '36.00', 'lng' => '51.40']); // ~33 km away

    $this->getJson(route('user.home.hallsInArea', [
        'lat' => 35.70, 'lng' => 51.40,
        'filters' => json_encode([]),
        'sorting' => json_encode([]),
    ]))
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

/**
 * Halls used by the DataTable cases below (user at 35.70/51.40):
 *   Alpha 35.70 → ~0 km, Beta 35.72 → ~2.2 km, Gamma 35.75 → ~5.6 km,
 *   Delta 36.00 → ~33 km (outside the radius).
 */
function seedNearbyHalls(): void
{
    Hall::factory()->create(['name' => 'Alpha Salon', 'lat' => '35.70', 'lng' => '51.40']);
    Hall::factory()->create(['name' => 'Beta Salon', 'lat' => '35.72', 'lng' => '51.40']);
    Hall::factory()->create(['name' => 'Gamma Salon', 'lat' => '35.75', 'lng' => '51.40']);
    Hall::factory()->create(['name' => 'Delta Salon', 'lat' => '36.00', 'lng' => '51.40']);
}

test('it exposes the DataTable meta with the total in-range count', function () {
    seedNearbyHalls();

    $this->getJson(route('user.home.hallsInArea', [
        'lat' => 35.70, 'lng' => 51.40,
        'filters' => json_encode([]),
        'sorting' => json_encode([]),
    ]))
        ->assertOk()
        ->assertJsonCount(3, 'data')          // Delta is out of range
        ->assertJsonPath('meta.totalRowCount', 3);
});

test('it paginates with start and size while reporting the full total', function () {
    seedNearbyHalls();

    $response = $this->getJson(route('user.home.hallsInArea', [
        'lat' => 35.70, 'lng' => 51.40, 'start' => 0, 'size' => 2,
        'filters' => json_encode([]),
        'sorting' => json_encode([]),
    ]));

    $response->assertOk()
        ->assertJsonCount(2, 'data')              // only the page is returned
        ->assertJsonPath('meta.totalRowCount', 3) // but the total still reflects all matches
        ->assertJsonPath('data.0.name', 'Alpha Salon')  // nearest first
        ->assertJsonPath('data.1.name', 'Beta Salon');
});

test('results are always ordered nearest-first, whatever sort the client asks for', function () {
    seedNearbyHalls();

    // Client asks for name descending, but distance is always the primary sort.
    $response = $this->getJson(route('user.home.hallsInArea', [
        'lat' => 35.70, 'lng' => 51.40,
        'filters' => json_encode([]),
        'sorting' => json_encode([['id' => 'name', 'desc' => true]]),
    ]));

    $response->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonPath('data.0.name', 'Alpha Salon')  // nearest
        ->assertJsonPath('data.1.name', 'Beta Salon')
        ->assertJsonPath('data.2.name', 'Gamma Salon'); // farthest in range
});

test('it can filter by distance', function () {
    seedNearbyHalls();

    $response = $this->getJson(route('user.home.hallsInArea', [
        'lat' => 35.70, 'lng' => 51.40,
        'filters' => json_encode([
            ['id' => 'distance', 'value' => 3, 'fn' => 'lessThan', 'datatype' => 'numeric'],
        ]),
        'sorting' => json_encode([]),
    ]));

    $response->assertOk()
        ->assertJsonCount(2, 'data')              // Alpha (~0) and Beta (~2.2) only
        ->assertJsonPath('meta.totalRowCount', 2)
        ->assertJsonPath('data.0.name', 'Alpha Salon')
        ->assertJsonPath('data.1.name', 'Beta Salon');
});

test('it can filter by a hall column', function () {
    seedNearbyHalls();

    $response = $this->getJson(route('user.home.hallsInArea', [
        'lat' => 35.70, 'lng' => 51.40,
        'filters' => json_encode([
            ['id' => 'name', 'value' => 'Beta', 'fn' => 'contains', 'datatype' => 'text'],
        ]),
        'sorting' => json_encode([]),
    ]));

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('meta.totalRowCount', 1)
        ->assertJsonPath('data.0.name', 'Beta Salon');
});
