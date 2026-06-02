<?php

use App\Expert\Controllers\AuthController;
use App\Expert\Controllers\ProfileController;
use App\Expert\Controllers\ReservationManagementController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')
    ->name('auth.')
    ->controller(AuthController::class)
    ->group(function () {
        Route::post('register', 'register')->name('register');
        Route::post('send_otp', 'sendOtp')->name('sendOtp');
        Route::post('login_with_otp', 'loginWithOtp')->name('loginWithOtp');
        Route::post('login_with_password', 'loginWithPassword')->name('loginWithPassword');
        Route::post('logout', 'logout')->middleware('auth:expert')->name('logout');
    });

Route::prefix('profile')
    ->name('profile.')
    ->controller(ProfileController::class)
    ->group(function () {
        Route::get('info', 'info')->name('info');
        Route::post('edit', 'edit')->name('edit');
        Route::post('change_password', 'changePassword')->name('changePassword');
        Route::post('change_avatar', 'changeAvatar')->name('changeAvatar');
        Route::post('upload_portfolio', 'uploadPortfolio')->name('uploadPortfolio');
        Route::post('define_working_hour', 'defineWorkingHour')->name('defineWorkingHour');
    });

Route::prefix('reservations')
    ->name('reservation.')
    ->controller(ReservationManagementController::class)
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::get('/{reservation}', 'show')->name('show');
        Route::post('/{reservation}', 'update')->name('update');
        Route::delete('/{reservation}', 'destroy')->name('destroy');
    });
