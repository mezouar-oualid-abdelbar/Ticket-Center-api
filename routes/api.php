<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\MessageController;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');



Route::apiResource('/messages', MessageController::class);

// Route::middleware('auth:sanctum')->group(function () {
//     Route::apiResource('messages', MessageController::class);
// });