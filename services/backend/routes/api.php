<?php

use App\Ai\Controller\AskProductController;
use App\Auth\Jwt\Controller\AuthController;
use App\Category\Controller\CategoryController;
use App\Http\Middleware\SetLocaleFromHeader;
use App\Product\Controller\ProductController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('v1.')->middleware(SetLocaleFromHeader::class)->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/{product}', [ProductController::class, 'show']);
    Route::post('/products/{product}/ask', AskProductController::class);

    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/categories/{category}', [CategoryController::class, 'show']);

    Route::middleware('auth:api')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});
