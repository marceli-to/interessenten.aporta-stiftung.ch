<?php

use App\Http\Controllers\Api\Dashboard\UserController;
use App\Http\Controllers\Api\V1\ApplicationStoreController;
use App\Http\Controllers\Api\V1\LookupController;
use Illuminate\Support\Facades\Route;

Route::prefix('dashboard')
	->middleware(['web', 'auth'])
	->group(function () {
		Route::controller(UserController::class)
			->prefix('users')
			->group(function () {
				Route::get('/', 'index');
				Route::post('/', 'store');
				Route::get('/{user}', 'show');
				Route::put('/{user}', 'update');
				Route::delete('/{user}', 'destroy');
			});
	});

Route::prefix('v1')
	->middleware('throttle:60,1')
	->group(function () {
		Route::get('lookups', [LookupController::class, 'show'])->name('api.lookups');
	});

Route::prefix('v1')
	->middleware(['auth.intake', 'throttle:intake', 'max.body'])
	->group(function () {
		Route::post('applications', [ApplicationStoreController::class, 'store'])->name('api.applications.store');
	});
