<?php

use App\Expert\Controllers\AuthController;
use App\Expert\Controllers\Manager\ExpertManagementController;
use App\Expert\Controllers\Manager\HallManagementController;
use App\Expert\Controllers\Manager\HallServicesManagementController;
use App\Expert\Controllers\Manager\ServiceManagementController;
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
    ->middleware(['auth:expert'])
    ->controller(ProfileController::class)
    ->group(function () {
        Route::get('info', 'info')->name('info');
        Route::post('update', 'update')->name('update');
        Route::post('change_password', 'changePassword')->name('changePassword');
        Route::post('change_avatar', 'changeAvatar')->name('changeAvatar');
        Route::post('upload_portfolio', 'uploadPortfolio')->name('uploadPortfolio');
        Route::post('define_working_hour', 'defineWorkingHour')->name('defineWorkingHour');
        Route::post('define_role', 'defineRole')->name('defineRole');
    });

Route::prefix('halls')
    ->name('hall.')
    ->middleware(['auth:expert'])
    ->controller(HallManagementController::class)
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::get('/{hall}', 'show')->name('show');
        Route::post('/{hall}', 'update')->name('update');
        Route::delete('/{hall}', 'destroy')->name('destroy');
        Route::get('/services/{hall}', 'services')->name('services');
    });

Route::prefix('reservations')
    ->name('reservation.')
    ->middleware(['auth:expert'])
    ->controller(ReservationManagementController::class)
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::get('/{reservation}', 'show')->name('show');
        Route::post('/{reservation}', 'update')->name('update');
        Route::delete('/{reservation}', 'destroy')->name('destroy');
    });

Route::prefix('service_categories')
    ->name('service_categories.')
    ->middleware(['auth:expert'])
    ->controller(ServiceManagementController::class)
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/list', 'list')->name('list');
        Route::post('/', 'store')->name('store');
        Route::get('/{reservation}', 'show')->name('show');
        Route::post('/{reservation}', 'update')->name('update');
        Route::delete('/{reservation}', 'destroy')->name('destroy');
    });

Route::prefix('staff')
    ->name('staff.')
    ->middleware(['auth:expert'])
    ->controller(ExpertManagementController::class)
    ->group(function () {
        Route::get('/{hall}', 'index')->name('index');
        Route::post('/{hall}', 'store')->name('store');
        Route::get('/{expert}', 'show')->name('show');
        Route::post('/{expert}', 'update')->name('update');
        Route::delete('/{expert}', 'destroy')->name('destroy');
    });

Route::prefix('services')
    ->name('services.')
    ->middleware(['auth:expert'])
    ->controller(HallServicesManagementController::class)
    ->group(function () {
        Route::get('/{hall}', 'index')->name('index');
        Route::post('/{hall}', 'store')->name('store');
        Route::get('/{service}', 'show')->name('show');
        Route::post('/{service}', 'update')->name('update');
        Route::delete('/{service}', 'destroy')->name('destroy');
    });
