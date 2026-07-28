<?php

use App\Http\Controllers\CityController;
use App\Http\Controllers\ProvinceController;
use Illuminate\Http\Request;
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
