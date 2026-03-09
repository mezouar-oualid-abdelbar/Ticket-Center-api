<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function getUserId(Request $request)
{
    return response()->json([
        'user_id' => $request->user()->id
    ]);
}
}
