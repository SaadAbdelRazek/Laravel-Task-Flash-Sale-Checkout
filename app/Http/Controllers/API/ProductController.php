<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Support\Facades\Cache;

class ProductController extends Controller
{
    public function show($id)
    {
        $cacheKey = "product_{$id}_summary";

        $data = Cache::remember($cacheKey, 5, function () use ($id) {
            $p = Product::findOrFail($id);

            return [
                'id'        => $p->id,
                'name'      => $p->name,
                'description' => $p->description,
                'price'     => $p->price,
                'stock' => $p->stock,
            ];
        });

        return response()->json($data);
    }
}