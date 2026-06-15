<?php

use App\Http\Controllers\Api\Dashboard\ApplicationBulkController;
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
				// Detail resolves soft-deleted rows too, so the "Gelöscht" list
				// can open a trashed application read-only. Update/destroy stay on
				// the default scope (they don't act on trashed records).
				Route::get('/{application}', 'show')->withTrashed();
				Route::put('/{application}', 'update');
				Route::delete('/{application}', 'destroy');
				// Restore acts on trashed rows, so the binding must resolve them.
				Route::post('/{application}/restore', 'restore')->withTrashed();
			});

		Route::put('applications/{application}/status', [ApplicationStatusController::class, 'update']);

		// Bulk operations over a selected set (explicit ids or filter + exclusions).
		Route::post('applications/bulk-delete', [ApplicationBulkController::class, 'destroy']);
		Route::post('applications/bulk-restore', [ApplicationBulkController::class, 'restore']);
		// Resolve a selection to the ordered id list seeding the browse set.
		Route::post('applications/bulk-resolve', [ApplicationBulkController::class, 'resolve']);
		// Synchronous PDF export of a selection (capped; streams the file back).
		Route::post('applications/bulk-export', [ApplicationBulkController::class, 'export']);

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
