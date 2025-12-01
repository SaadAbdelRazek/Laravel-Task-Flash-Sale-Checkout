<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\API\ProductController;
use App\Http\Controllers\API\HoldController;
use App\Http\Controllers\API\OrderController;
use App\Http\Controllers\API\PaymentWebhookController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::get('/products/{id}', [ProductController::class, 'show']);

// Flash-Sale Holds
Route::post('/holds', [HoldController::class, 'store']);

// Convert Hold to Order
Route::post('/orders', [OrderController::class, 'store']);

// Payment Webhook (Idempotent)
Route::post('/payments/webhook', [PaymentWebhookController::class, 'handle']);
