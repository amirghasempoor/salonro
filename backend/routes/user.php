<?php

use App\User\Controllers\DiscountController;
use Illuminate\Support\Facades\Route;
use User\Application\Http\Controllers\AuthController;
use User\Application\Http\Controllers\HomePageController;
use User\Application\Http\Controllers\ProfileController;
use User\Application\Http\Controllers\ReservationManagementController;

Route::prefix('home')
    ->name('home.')
    ->controller(HomePageController::class)
    ->group(function () {
        Route::get('halls', 'hallsInArea')->name('hallsInArea');
        Route::get('halls/{hall}', 'hallDetails')->name('hallDetails');
        Route::get('halls/services/{hall}', 'hallServices')->name('hallServices');
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
        Route::post('complete', 'complete')->name('complete');
        Route::post('edit', 'edit')->name('edit');
        Route::post('change_password', 'changePassword')->name('changePassword');
        Route::post('change_avatar', 'changeAvatar')->name('changeAvatar');
    });

Route::prefix('reservations')
    ->name('reservation.')
//    ->middleware('auth:user')
    ->controller(ReservationManagementController::class)
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::get('/staff/schedule', 'staffSchedule')->name('staffSchedule');
        Route::get('/staff/{hall}', 'staff')->name('staff');
        Route::get('/staff/working_hours/{hall}/{expert}', 'staffWorkingHours')->name('staffWorkingHours');
        Route::get('/{reservation}', 'show')->name('show')->can('userReservation.show', 'reservation');
        Route::post('/{reservation}', 'update')->name('update')->can('userReservation.update', 'reservation');
        Route::delete('/{reservation}', 'destroy')->name('destroy')->can('userReservation.delete', 'reservation');
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
