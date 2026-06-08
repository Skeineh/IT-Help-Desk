<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\TicketController;
use App\Models\Category;
use App\Models\Priority;
use App\Models\Status;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Tymon\JWTAuth\Facades\JWTAuth;

Route::prefix('auth')->group(function () {
    Route::post('login',    [AuthController::class, 'login']);
    Route::post('logout',   [AuthController::class, 'logout'])->middleware('auth:api');
    Route::get('me',        [AuthController::class, 'me'])->middleware('auth:api');
    Route::post('register', [AuthController::class, 'register'])->middleware(['auth:api', 'role:Admin']);
});

Route::middleware('auth:api')->group(function () {

    Route::apiResource('tickets', TicketController::class);

    // Lookup endpoints for dropdown population
    Route::get('categories', fn () =>
        response()->json(Category::where('IsActive', true)->get())
    );

    Route::get('priorities', fn () =>
        response()->json(Priority::orderBy('PriorityLevel')->get())
    );

    Route::get('statuses', fn () =>
        response()->json(Status::orderBy('StatusNumber')->get())
    );

    Route::get('agents', function () {
        $user = JWTAuth::parseToken()->authenticate();
        $user->loadMissing('role');

        if (!in_array($user->role->RoleName, ['Manager', 'Admin'])) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        return response()->json(
            User::whereHas('role', fn ($q) => $q->where('RoleName', 'Agent'))
                ->get(['UserNumber', 'FullName', 'Email', 'Department'])
        );
    });

});
