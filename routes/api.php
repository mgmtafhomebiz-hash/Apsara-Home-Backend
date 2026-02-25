<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MemberController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PaymentController;

// Public auth routes
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login',    [AuthController::class, 'login']);
});

Route::post('/payments/checkout-session', [PaymentController::class, 'createCheckoutSession']);
Route::get('/payments/checkout-session/{checkoutId}', [PaymentController::class, 'verifyCheckoutSession']);


// Protected routes (requires Sanctum token)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me',      [AuthController::class, 'me']);
    Route::put('/auth/me',      [AuthController::class, 'updateMe']);
    Route::get('/admin/members', [MemberController::class, 'index']);
    Route::get('/admin/members/stats', [MemberController::class, 'stats']);
});
