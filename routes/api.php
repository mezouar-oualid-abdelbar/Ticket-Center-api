<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\MessageController;
use App\Events\MessageNotification;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::get('/event' , function(){
    event(new MessageNotification('this is our first broadcast messaage'));
});
// Route::get('/listen' , function(){
//     event(new MessageNotification('this is our first broadcast messaage'));
// });
// Route::apiResource('/messages', MessageController::class);

// Route::middleware('auth:sanctum')->group(function () {
//     Route::apiResource('messages', MessageController::class);
// });
