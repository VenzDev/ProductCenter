<?php

declare(strict_types=1);

use App\Auth\Jwt\Controller\AuthController;
use App\BlogPost\Controller\BlogPostController;
use App\Category\Controller\CategoryController;
use App\Category\Controller\CategoryProductsController;
use App\Http\Middleware\SetLocaleFromHeader;
use App\Product\Controller\AskProductController;
use App\Product\Controller\ProductController;
use App\Product\Controller\SearchProductsController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('v1.')->middleware(SetLocaleFromHeader::class)->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/latest', [ProductController::class, 'latest']);
    Route::get('/products/search', SearchProductsController::class);
    Route::get('/products/{product}', [ProductController::class, 'show']);
    Route::post('/products/{product}/ask', AskProductController::class);

    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/categories/{category}', [CategoryController::class, 'show']);
    Route::get('/categories/{category:slug}/products', [CategoryProductsController::class, 'index'])
        ->where('category', '.*');

    Route::get('/blog-posts', [BlogPostController::class, 'index']);
    Route::get('/blog-posts/{blogPost:slug}', [BlogPostController::class, 'show']);

    Route::middleware('auth:api')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});
