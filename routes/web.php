<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
	return view('pages.welcome');
})->name('page.welcome');

Route::middleware('auth')->group(function () {
	Route::get('/dashboard/{any?}', function () {
		return view('components.layout.app');
	})->where('any', '.*')->name('dashboard');
});

require __DIR__.'/auth.php';
