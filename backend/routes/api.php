<?php

use App\Http\Controllers\CityController;
use App\Http\Controllers\ProfessionController;
use App\Http\Controllers\ProvinceController;
use App\Http\Controllers\ServiceController;
use Illuminate\Support\Facades\Route;

Route::prefix('provinces')
    ->controller(ProvinceController::class)
    ->name('provinces.')
    ->group(function () {
        Route::get('/list', 'list')->name('list');
    });

Route::prefix('cities')
    ->controller(CityController::class)
    ->name('cities.')
    ->group(function () {
        Route::get('/{province_id}', 'list')->name('list');
    });

Route::prefix('professions')
    ->controller(ProfessionController::class)
    ->name('professions.')
    ->group(function () {
        Route::get('/list', 'list')->name('list');
    });

Route::prefix('services')
    ->controller(ServiceController::class)
    ->name('services.')
    ->group(function () {
        Route::get('/list', 'list')->name('list');
    });
