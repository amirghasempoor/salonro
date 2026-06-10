<?php

use App\User\Controllers\AuthController;
use App\User\Controllers\ProfileController;
use App\User\Controllers\ReservationManagementController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')
    ->name('auth.')
    ->controller(AuthController::class)
    ->group(function () {
        Route::post('register', 'register')->name('register');
        Route::post('send_otp', 'sendOtp')->name('sendOtp');
        Route::post('login_with_otp', 'loginWithOtp')->name('loginWithOtp');
        Route::post('login_with_password', 'loginWithPassword')->name('loginWithPassword');
        Route::post('logout', 'logout')->middleware('auth:web')->name('logout');
    });

Route::prefix('profile')
    ->name('profile.')
    ->middleware('auth:web')
    ->controller(ProfileController::class)
    ->group(function () {
        Route::get('info', 'info')->name('info');
        Route::post('edit', 'edit')->name('edit');
        Route::post('change_password', 'changePassword')->name('changePassword');
    });

Route::prefix('reservations')
    ->name('reservation.')
    ->middleware('auth:web')
    ->controller(ReservationManagementController::class)
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::get('/{reservation}', 'show')->name('show');
        Route::post('/{reservation}', 'update')->name('update');
        Route::delete('/{reservation}', 'destroy')->name('destroy');
    });
