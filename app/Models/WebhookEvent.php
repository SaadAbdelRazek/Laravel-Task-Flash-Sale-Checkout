<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebhookEvent extends Model
{
    protected $fillable = [
        'idempotency_key',
        'type',
        'payload',
        'status'
    ];

    protected $casts = [
        'payload' => 'array',
    ];
}