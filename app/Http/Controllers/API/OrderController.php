<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Hold;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function store(Request $r)
    {
        $r->validate([
            'hold_id' => 'required|exists:holds,id',
        ]);

        $holdId = $r->hold_id;

        return DB::transaction(function () use ($holdId) {

            $hold = Hold::where('id', $holdId)->lockForUpdate()->first();

            if (!$hold) {
                return response()->json(['message' => 'Hold not found'], 404);
            }

            if ($hold->status !== 'active') {
                return response()->json(['message' => 'Hold not active'], 400);
            }

            // Mark hold as used
            $hold->status = 'used';
            $hold->used_at = now();
            $hold->save();

            // Create order
            $product = $hold->product()->lockForUpdate()->first();

            $total = $product->price * $hold->qty;

            $order = Order::create([
                'hold_id' => $hold->id,
                'status'  => 'pre_payment',
                'total'   => $total,
            ]);

            // Transfer reserved --> stock
            $product->reserved = max(0, $product->reserved - $hold->qty);
            $product->stock    = max(0, $product->stock - $hold->qty);
            $product->save();

            return response()->json([
                'order_id' => $order->id,
                'status'   => $order->status,
                'total'    => $order->total,
            ], 201);
        }, 5);
    }
}