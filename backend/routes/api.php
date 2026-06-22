<?php

use App\Http\Controllers\AiController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TicketAttachmentController;
use App\Http\Controllers\TicketCommentController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\TicketHistoryController;
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
});

Route::middleware('auth:api')->group(function () {
    Route::post('change-password', [AuthController::class, 'changePassword']);
});

Route::middleware(['auth:api', 'password.changed'])->group(function () {
    Route::prefix('admin')->middleware('role:Admin')->group(function () {
        Route::post('users', [AdminUserController::class, 'store']);
        Route::get('roles', [AdminUserController::class, 'roles']);
    });

    Route::get('dashboard/stats', [DashboardController::class, 'stats']);

    Route::get('notifications', [NotificationController::class, 'index']);
    Route::get('notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::put('notifications/{notification}/read', [NotificationController::class, 'markRead']);
    Route::put('notifications/read-all', [NotificationController::class, 'markAllRead']);
    Route::delete('notifications/{notification}', [NotificationController::class, 'destroy']);

    Route::get('tickets/{ticket}/history', [TicketHistoryController::class, 'index']);
    Route::get('tickets/{ticket}/comments', [TicketCommentController::class, 'index']);
    Route::post('tickets/{ticket}/comments', [TicketCommentController::class, 'store']);
    Route::delete('comments/{comment}', [TicketCommentController::class, 'destroy']);

    Route::get('tickets/{ticket}/attachments', [TicketAttachmentController::class, 'index']);
    Route::post('tickets/{ticket}/attachments', [TicketAttachmentController::class, 'store']);
    Route::get('attachments/{attachment}/download', [TicketAttachmentController::class, 'download']);
    Route::delete('attachments/{attachment}', [TicketAttachmentController::class, 'destroy']);

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

    // AI endpoints
    Route::post('ai/categorize',      [AiController::class, 'categorize']);
    Route::post('ai/priority',        [AiController::class, 'detectPriority']);
    Route::post('ai/chat',            [AiController::class, 'chat']);
    Route::post('ai/summarize',       [AiController::class, 'summarize']);
    Route::post('ai/troubleshoot',    [AiController::class, 'troubleshoot']);

    // Report export endpoints
    Route::get('reports/tickets/pdf',   [ReportController::class, 'exportPdf']);
    Route::get('reports/tickets/excel', [ReportController::class, 'exportExcel']);

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
