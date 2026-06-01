<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('login',    [AuthController::class, 'login']);
    Route::post('logout',   [AuthController::class, 'logout'])->middleware('auth:api');
    Route::get('me',        [AuthController::class, 'me'])->middleware('auth:api');
    Route::post('register', [AuthController::class, 'register'])->middleware(['auth:api', 'role:Admin']);
});
