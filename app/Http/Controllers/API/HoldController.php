<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Hold;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Jobs\ExpireHoldJob;

class HoldController extends Controller
{
    public function store(Request $r)
    {
        $r->validate([
            'product_id' => 'required|exists:products,id',
            'qty' => 'required|integer|min:1',
        ]);

        $productId = $r->product_id;
        $qty = (int) $r->qty;

        return DB::transaction(function () use ($productId, $qty) {
            // Lock product row FOR UPDATE
            $product = Product::where('id', $productId)->lockForUpdate()->first();

            if (!$product) {
                return response()->json(['message' => 'Product not found'], 404);
            }

            $available = $product->stock - $product->reserved;

            if ($qty > $available) {
                return response()->json([
                    'message'   => 'Insufficient stock',
                    'available' => $available
                ], 409);
            }

            // Create Hold
            $expiresAt = Carbon::now()->addMinutes(2);

            $hold = Hold::create([
                'product_id' => $product->id,
                'qty'        => $qty,
                'status'     => 'active',
                'expires_at' => $expiresAt,
            ]);

            // Reserve stock
            $product->reserved += $qty;
            $product->save();

            // Schedule expiry job
            ExpireHoldJob::dispatch($hold->id)->delay($expiresAt);

            return response()->json([
                'hold_id'    => $hold->id,
                'expires_at' => $expiresAt->toDateTimeString(),
            ], 201);
        }, 5);
    }
}