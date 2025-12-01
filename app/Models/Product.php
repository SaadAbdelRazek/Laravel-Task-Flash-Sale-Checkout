<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'description',
        'price',
        'stock',
        'reserved'
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    public function holds()
    {
        return $this->hasMany(Hold::class);
    }

    // Available stock = total stock - reserved
    public function getAvailableAttribute()
    {
        return max(0, $this->stock - $this->reserved);
    }
}