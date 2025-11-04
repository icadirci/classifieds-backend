<?php

use App\Http\Controllers\Api\Admin\ListingApprovalController;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Category\CategoryController;
use App\Http\Controllers\Api\Listing\ListingController;
use App\Http\Controllers\Api\Subcategory\SubcategoryController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::apiResource('categories', CategoryController::class)->only(['index', 'show']);
Route::apiResource('subcategories', SubcategoryController::class)->only(['index', 'show']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::apiResource('listings', ListingController::class);
    Route::apiResource('categories', CategoryController::class)->only(['store', 'update', 'destroy']);
    Route::apiResource('subcategories', SubcategoryController::class)->only(['store', 'update', 'destroy']);
});

Route::prefix('admin')->middleware('auth:sanctum')->group(function () {
    Route::get('/listings/pending', [ListingApprovalController::class, 'index']);
    Route::post('/listings/{listing}/approve', [ListingApprovalController::class, 'approve']);
    Route::post('/listings/{listing}/reject', [ListingApprovalController::class, 'reject']);
});
