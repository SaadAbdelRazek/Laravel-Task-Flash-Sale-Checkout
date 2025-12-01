<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Hold extends Model
{
    protected $fillable = [
        'product_id',
        'qty',
        'status',
        'expires_at',
        'used_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
    ];


    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function isActive()
    {
        return $this->status === 'active'
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }
}