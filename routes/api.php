<?php

use Illuminate\Support\Facades\Route;

Route::prefix('dashboard')
	->middleware(['web', 'auth'])
	->group(function () {
		//
	});
