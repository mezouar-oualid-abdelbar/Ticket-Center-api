<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\{
    AssignmentController,
    AuthController,
    InterventionController,
    MessageController,
    TicketController,
    UserController,
};

/*
|--------------------------------------------------------------------------
| Public
|--------------------------------------------------------------------------
*/
Route::post('/register',            [AuthController::class, 'register']);
Route::post('/verify-email',        [AuthController::class, 'verifyEmail']);
Route::post('/resend-verification', [AuthController::class, 'resendVerification']);
Route::post('/login',               [AuthController::class, 'login']);
Route::post('/forgot-password',     [AuthController::class, 'forgotPassword']);
Route::post('/reset-password',      [AuthController::class, 'resetPassword']);

Route::get('/login', fn() => response()->json(['message' => 'unauthenticated']))->name('login');

/*
|--------------------------------------------------------------------------
| Protected — all authenticated users
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::get('/me',      [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Tickets created by this user
    Route::get('/ticket',  [TicketController::class, 'index']);
    Route::post('/ticket', [TicketController::class, 'create']);

    // All unresolved tickets where user is reporter, leader, or technician
    // Used by HomePage and Messages dropdown
    Route::get('/my-tickets', [TicketController::class, 'myTickets']);

    // Chat messages per ticket
    Route::get('/messages/{ticketId}',  [MessageController::class, 'index']);
    Route::post('/messages/{ticketId}', [MessageController::class, 'store']);

    // Reverb private-channel auth (Sanctum Bearer token required)
    Route::post('/broadcasting/auth', function (Request $request) {
        return Broadcast::auth($request);
    });

});

/*
|--------------------------------------------------------------------------
| Manager / Admin
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'role:manager|admin'])
    ->prefix('manager')
    ->group(function () {
        Route::get('/ticket',               [TicketController::class, 'all']);
        Route::get('/ticket/{id}',          [TicketController::class, 'show']);
        Route::get('/technicians',          [UserController::class, 'technicians']);
        Route::post('/ticket/{id}/assign',  [AssignmentController::class, 'assign']);
        Route::get('/ticket/{id}/progress', [TicketController::class, 'progress']);
    });

/*
|--------------------------------------------------------------------------
| Technician / Admin
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'role:technician|admin'])
    ->prefix('technician')
    ->group(function () {
        Route::get('/assignments',                 [AssignmentController::class, 'assigments']);
        Route::get('/assignment/{id}',             [AssignmentController::class, 'assigment']);
        Route::post('/appointment',                [InterventionController::class, 'makeAppointment']);
        Route::post('/{id}/intervention/complete', [InterventionController::class, 'complete']);
        Route::post('/{id}/intervention/update',   [InterventionController::class, 'update']);
    });
