<?php

use App\Http\Api\Controllers\LoginController;
use App\Http\Api\Controllers\RegisterController;
use App\Http\Api\Controllers\ChatController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/register', [RegisterController::class, 'register']);
Route::post('/login', [LoginController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::post('/logout', [LoginController::class, 'logout']);

    // Chat con OpenAI (Layla)
    Route::post('/chat', [ChatController::class, 'chat']);
});
