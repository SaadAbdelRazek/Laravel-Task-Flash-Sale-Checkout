<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        Product::create([
            'name'        => 'Flash Sale Item',
            'description' => 'Limited stock item for flash-sale testing.',
            'price'       => 199.99,
            'stock'       => 10,
            'reserved'    => 0,
        ]);
    }
}
