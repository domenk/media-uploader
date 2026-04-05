<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\MediaController;

Route::post('user/create', [UserController::class, 'store']);

Route::post('user/show', [UserController::class, 'show']);

Route::post('user/tokens/create', [UserController::class, 'tokensCreate']);

Route::get('media', [MediaController::class, 'index'])->middleware('auth:sanctum');

Route::post('media', [MediaController::class, 'store'])->middleware('auth:sanctum');

Route::get('media/{id}', [MediaController::class, 'show'])->middleware('auth:sanctum');

Route::get('media/{id}/status', [MediaController::class, 'getStatus'])->middleware('auth:sanctum');
