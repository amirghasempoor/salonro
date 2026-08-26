<?php

use App\Expert\Controllers\AuthController;
use App\Expert\Controllers\JobOfferController;
use App\Expert\Controllers\Manager\DiscountManagementController;
use App\Expert\Controllers\Manager\ExpertManagementController;
use App\Expert\Controllers\Manager\HallManagementController;
use App\Expert\Controllers\Manager\HallServicesManagementController;
use App\Expert\Controllers\Manager\JobOfferController as ManagerJobOfferController;
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
        Route::post('complete', 'complete')->name('complete');
        Route::post('update', 'update')->name('update');
        Route::post('change_password', 'changePassword')->name('changePassword');
        Route::post('upload_portfolio', 'uploadPortfolio')->name('uploadPortfolio');
        Route::post('define_role', 'defineRole')->name('defineRole');
        Route::post('define_working_hour/{hall}', 'defineWorkingHour')->name('defineWorkingHour');
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
        Route::get('/{hall}', 'index')->name('index');
        Route::post('/{hall}', 'store')->name('store');
        Route::get('/details/{reservation}', 'show')->name('show');
        Route::post('/{hall}/{reservation}', 'update')->name('update');
        Route::delete('/{hall}/{reservation}', 'destroy')->name('destroy');
    });

Route::prefix('service_categories')
    ->name('service_categories.')
    ->middleware(['auth:expert'])
    ->controller(ServiceManagementController::class)
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/list', 'list')->name('list');
        Route::post('/', 'store')->name('store');
        Route::get('/{category}', 'show')->name('show');
        Route::post('/{category}', 'update')->name('update');
        Route::delete('/{category}', 'destroy')->name('destroy');
    });

Route::prefix('staff')
    ->name('staff.')
    ->middleware(['auth:expert'])
    ->controller(ExpertManagementController::class)
    ->group(function () {
        Route::get('/list/{hall}', 'list')->name('list')->where('hall', '[0-9]+');
        Route::get('/{hall}', 'index')->name('index');
        Route::post('/{hall}', 'store')->name('store');
        Route::get('/details/{expert}', 'show')->name('show');
        Route::post('/{hall}/{expert}', 'update')->name('update');
        Route::delete('/{hall}/{expert}', 'destroy')->name('destroy');
    });

Route::prefix('services')
    ->name('services.')
    ->middleware(['auth:expert'])
    ->controller(HallServicesManagementController::class)
    ->group(function () {
        Route::get('/{hall}', 'index')->name('index');
        Route::post('/{hall}', 'store')->name('store');
        Route::get('/{hall}/{hallService}', 'show')->name('show');
        Route::post('/{hall}/{hallService}', 'update')->name('update');
        Route::delete('/{hall}/{hallService}', 'destroy')->name('destroy');
    });

Route::prefix('discounts')
    ->name('discount.')
    ->middleware(['auth:expert'])
    ->controller(DiscountManagementController::class)
    ->group(function () {
        Route::get('/{hall}', 'index')->name('index');
        Route::post('/{hall}', 'store')->name('store');
        Route::get('/details/{hall}/{discount}', 'show')->name('show');
        Route::post('/{hall}/{discount}', 'update')->name('update');
        Route::delete('/{hall}/{discount}', 'destroy')->name('destroy');
    });

Route::prefix('job_offers')
    ->name('job_offers.')
    ->middleware(['auth:expert'])
    ->controller(ManagerJobOfferController::class)
    ->group(function () {
        Route::get('/{hall}', 'index')->name('index');
        Route::post('/{hall}', 'store')->name('store');
        Route::get('/details/{jobOffer}', 'show')->name('show');
        Route::post('/update/{jobOffer}', 'update')->name('update');
        Route::delete('/delete/{jobOffer}', 'destroy')->name('destroy');
        Route::get('/applications/{jobOffer}', 'applications')->name('applications');
        Route::post('/accept/{application}', 'acceptApplication')->name('acceptApplication');
        Route::post('/reject/{application}', 'rejectApplication')->name('rejectApplication');
    });

Route::prefix('jobs')
    ->name('jobs.')
    ->middleware(['auth:expert'])
    ->controller(JobOfferController::class)
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/my_applications', 'myApplications')->name('myApplications');
        Route::get('/{jobOffer}', 'show')->name('show');
        Route::post('/apply/{jobOffer}', 'apply')->name('apply');
    });
