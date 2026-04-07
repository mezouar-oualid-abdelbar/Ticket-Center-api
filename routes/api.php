<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Events\MessageNotification;

use App\Http\Controllers\{
    AssignmentController,
    AuthController,
    InterventionController,
    TicketController,
    UserController,
};

/*
|--------------------------------------------------------------------------
| Public
|--------------------------------------------------------------------------
*/
Route::get('/event' , function(){
    event(new MessageNotification('this is our first broadcast messaage'));
});

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/login', function () {
    return response()->json([
        'message' => 'unauthenticated'
    ]);
})->name('login');

/*
|--------------------------------------------------------------------------
| Protected Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Auth
    |--------------------------------------------------------------------------
    */

    Route::middleware('auth:sanctum')->get('/me', [AuthController::class, 'me']);

    Route::post('/logout', [AuthController::class, 'logout']);

    /*
    |--------------------------------------------------------------------------
    | Default
    |--------------------------------------------------------------------------
    */

    Route::get('/ticket',         [TicketController::class, 'index']);

    Route::post('/ticket',        [TicketController::class, 'create']);




});

/*
|--------------------------------------------------------------------------
| Manager
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum', 'role:manager|admin'])
    ->prefix('manager')
    ->group(function () {

        Route::get('/ticket', [TicketController::class, 'all']);

        Route::get('/ticket/{id}', [TicketController::class, 'show']);

        Route::get('/technicians', [UserController::class, 'technicians']);

        Route::post('/ticket/{id}/assign', [AssignmentController::class, 'assign']);

        Route::get('/ticket/{id}/progress', [TicketController::class, 'progress']);



});


/*
|--------------------------------------------------------------------------
| technician
|--------------------------------------------------------------------------
*/


Route::middleware(['auth:sanctum', 'role:technician|admin'])
    ->prefix('technician')
    ->group(function () {

    Route::get('/assigments', [AssignmentController::class, 'assigments']); 

    Route::get('/assigment/{id}', [AssignmentController::class, 'assigment']); 

    Route::post('/appointment', [InterventionController::class, 'makeAppointment']);



});


/*
|--------------------------------------------------------------------------
| Admin
|--------------------------------------------------------------------------
*/

// Route::middleware(['auth:sanctum','role:admin'])->prefix('admin')->group(function () {



// });
