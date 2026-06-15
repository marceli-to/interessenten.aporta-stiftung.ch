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

// Layout-Vorschau des PDF-Dossiers im Browser (nur lokal). Rendert dieselbe
// Blade-View wie der spätere Export, mit Dummy-Daten – schneller Feedback-Loop
// ohne PDF-Generierung. Vor dem Deploy wieder entfernen.
if (app()->environment('local')) {
	Route::get('/dev/pdf-vorschau', function () {
		return view('pdf.application', require resource_path('views/pdf/_preview_data.php'));
	});
}

require __DIR__.'/auth.php';
