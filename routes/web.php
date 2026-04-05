<?php

use Illuminate\Support\Facades\Route;

Route::get('login', function () {
	return response()->json(array('error' => 'unauthorized'), 401);
})->name('login');
