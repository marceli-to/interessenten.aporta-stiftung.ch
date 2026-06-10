<?php

use App\Http\Controllers\Api\Dashboard\ApplicationController;
use App\Http\Controllers\Api\Dashboard\ApplicationStatusController;
use App\Http\Controllers\Api\Dashboard\CurrentUserController;
use App\Http\Controllers\Api\Dashboard\NoteController;
use App\Http\Controllers\Api\Dashboard\UserController;
use App\Http\Controllers\Api\V1\ApplicationStoreController;
use App\Http\Controllers\Api\V1\LookupController;
use Illuminate\Support\Facades\Route;

Route::prefix('dashboard')
	->middleware(['web', 'auth'])
	->group(function () {
		Route::get('me', CurrentUserController::class);

		Route::controller(ApplicationController::class)
			->prefix('applications')
			->group(function () {
				Route::get('/', 'index');
				Route::get('/{application}', 'show');
				Route::put('/{application}', 'update');
				Route::delete('/{application}', 'destroy');
			});

		Route::put('applications/{application}/status', [ApplicationStatusController::class, 'update']);

		// {note} is scope-bound to {application} so a note from another
		// application resolves to 404 rather than being editable here.
		Route::controller(NoteController::class)
			->prefix('applications/{application}/notes')
			->scopeBindings()
			->group(function () {
				Route::post('/', 'store');
				Route::put('/{note}', 'update');
				Route::delete('/{note}', 'destroy');
			});

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
