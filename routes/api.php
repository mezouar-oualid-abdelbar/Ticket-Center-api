<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TicketController;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');
use App\Http\Controllers\Api\MessageController;
Route::apiResource('messages', MessageController::class);
// Route::middleware('auth:sanctum')->group(function () {
//     Route::apiResource('messages', MessageController::class);
// });