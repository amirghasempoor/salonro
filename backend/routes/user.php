<?php

use App\User\Controllers\AuthController;
use App\User\Controllers\DiscountController;
use App\User\Controllers\HomePageController;
use App\User\Controllers\ProfileController;
use App\User\Controllers\ReservationManagementController;
use Illuminate\Support\Facades\Route;

Route::prefix('home')
    ->name('home.')
    ->controller(HomePageController::class)
    ->group(function () {
        Route::get('halls', 'hallsInArea')->name('hallsInArea');
    });

Route::prefix('auth')
    ->name('auth.')
    ->controller(AuthController::class)
    ->group(function () {
        Route::post('register', 'register')->name('register');
        Route::post('send_otp', 'sendOtp')->name('sendOtp');
        Route::post('login_with_otp', 'loginWithOtp')->name('loginWithOtp');
        Route::post('login_with_password', 'loginWithPassword')->name('loginWithPassword');
        Route::post('logout', 'logout')->middleware('auth:user')->name('logout');
    });

Route::prefix('profile')
    ->name('profile.')
    ->middleware('auth:user')
    ->controller(ProfileController::class)
    ->group(function () {
        Route::get('info', 'info')->name('info');
        Route::post('edit', 'edit')->name('edit');
        Route::post('change_password', 'changePassword')->name('changePassword');
    });

Route::prefix('reservations')
    ->name('reservation.')
    ->middleware('auth:user')
    ->controller(ReservationManagementController::class)
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::get('/{reservation}', 'show')->name('show');
        Route::post('/{reservation}', 'update')->name('update');
        Route::delete('/{reservation}', 'destroy')->name('destroy');
    });

Route::prefix('discounts')
    ->name('discount.')
    ->middleware('auth:user')
    ->controller(DiscountController::class)
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/available', 'available')->name('available');
        Route::get('/{discount}', 'show')->name('show');
    });
