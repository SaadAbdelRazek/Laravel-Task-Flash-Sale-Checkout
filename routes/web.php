<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\App;

Route::get('/', function () {
    return response()->json([
        'meta' => [
            'service'     => 'Flash Sale Checkout API',
            'status'      => 'Operational 🟢',
            'environment' => App::environment(),
            'version'     => '1.0.0',
            'laravel'     => App::version(),
            'php'         => phpversion(),
        ],

        'docs' => [
            'readme'  => 'Please refer to README.md for setup instructions.',
            'postman' => 'Postman collection is available in /docs folder.',
        ],

        'endpoints' => [
            'check_product' => [
                'method'      => 'GET',
                'url'         => url('/api/products/{id}'),
                'description' => 'Get product details and real-time stock.'
            ],
            'create_hold' => [
                'method'      => 'POST',
                'url'         => url('/api/holds'),
                'description' => 'Atomically reserve stock (expires in 2 mins).'
            ],
            'create_order' => [
                'method'      => 'POST',
                'url'         => url('/api/orders'),
                'description' => 'Convert an active hold into a final order.'
            ],
            'payment_webhook' => [
                'method'      => 'POST',
                'url'         => url('/api/payments/webhook'),
                'description' => 'Handle payment provider callbacks (Idempotent).'
            ],
        ]
    ], 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
});
