<?php

use App\Models\Expert;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $expert = Expert::factory()->create();
    Sanctum::actingAs($expert, ['*'], 'expert');
});

test('portfolio is required', function () {
    $response = $this->postJson(route('expert.profile.uploadPortfolio'));

    $response->assertStatus(422);
    $response->assertJsonValidationErrorFor('portfolio');
});

test('portfolio should be array', function () {
    $response = $this->postJson(route('expert.profile.uploadPortfolio'), [
        'portfolio' => fake()->lexify()
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'portfolio' => __('validation.array', ['attribute' => 'portfolio']),
    ]);
});

test('expert can upload the portfolio', function () {
    $response = $this->postJson(route('expert.profile.uploadPortfolio'), [
        'portfolio' => [
            [
                'title' => 'title1',
                'image' => \Illuminate\Http\UploadedFile::fake()->image('portfolio1.jpg'),
            ],
            [
                'title' => 'title2',
                'image' => \Illuminate\Http\UploadedFile::fake()->image('portfolio2.jpg'),
            ]
        ],
    ]);

    $response->assertOk();

    $response->assertExactJson([
        'message' => __('messages.successful'),
    ]);

    $this->assertDatabaseCount('images', 2);

    $this->assertDatabaseHas('images', [
        'title' => 'title1',
    ]);

    $this->assertDatabaseHas('images', [
        'title' => 'title2',
    ]);
});
