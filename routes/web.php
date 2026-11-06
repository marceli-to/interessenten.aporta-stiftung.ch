<?php

use App\Http\Controllers\RenewalController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
	return view('pages.welcome');
})->name('page.welcome');

// Links from the renewal link email (public, token-authorized).
Route::prefix('verlaengerung')
	->middleware('throttle:30,1')
	->controller(RenewalController::class)
	->group(function () {
		Route::get('erledigt', 'done')->name('renewal.done');

		Route::get('{token}/bestaetigen', 'confirm')->name('renewal.confirm');
		Route::post('{token}/bestaetigen', 'storeConfirm')->name('renewal.confirm.store');
		Route::get('{token}/loeschen', 'delete')->name('renewal.delete');
		Route::post('{token}/loeschen', 'storeDelete')->name('renewal.delete.store');
	});

Route::middleware('auth')->group(function () {
	Route::get('/dashboard/{any?}', function () {
		return view('components.layout.app');
	})->where('any', '.*')->name('dashboard');
});

require __DIR__.'/auth.php';
