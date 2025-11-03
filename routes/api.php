<?php

use App\Http\Controllers\Api\Admin\ListingApprovalController;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Listing\ListingController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::apiResource('listings', ListingController::class);
});

Route::prefix('admin')->middleware('auth:sanctum')->group(function () {
    Route::get('/listings/pending', [ListingApprovalController::class, 'index']);
    Route::post('/listings/{listing}/approve', [ListingApprovalController::class, 'approve']);
    Route::post('/listings/{listing}/reject', [ListingApprovalController::class, 'reject']);
});
