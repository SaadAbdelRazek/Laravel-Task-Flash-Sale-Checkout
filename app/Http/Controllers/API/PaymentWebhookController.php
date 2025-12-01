<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\WebhookEvent;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentWebhookController extends Controller
{
    public function handle(Request $r)
    {
        $r->validate([
            'idempotency_key' => 'required|string',
            'order_id'        => 'required|integer',
            'status'          => 'required|string',   // success | failure
        ]);

        $key     = $r->idempotency_key;
        $orderId = $r->order_id;
        $status  = $r->status;

        return DB::transaction(function () use ($key, $orderId, $status, $r) {

            // Find or create webhook event
            $webhook = WebhookEvent::where('idempotency_key', $key)->lockForUpdate()->first();

            if ($webhook && $webhook->status === 'handled') {
                return response()->json(['message' => 'Already handled'], 200);
            }

            if (!$webhook) {
                $webhook = WebhookEvent::create([
                    'idempotency_key' => $key,
                    'type'            => 'payment',
                    'payload'         => $r->all(),
                    'status'          => 'processing',
                ]);
            } else {
                $webhook->payload = $r->all();
                $webhook->status  = 'processing';
                $webhook->save();
            }

            // Get order
            $order = Order::where('id', $orderId)->lockForUpdate()->first();

            if (!$order) {
                // order not created yet --> ask provider to retry later
                return response()->json(['message' => 'Order not found yet'], 202);
            }

            // already paid?
            if ($order->status === 'paid') {
                $webhook->status = 'handled';
                $webhook->save();
                return response()->json(['message' => 'Order already paid'], 200);
            }

            if ($status === 'success') {

                $order->status  = 'paid';
                $order->paid_at = now();
                $order->save();
            } else { // failure

                $order->status = 'cancelled';
                $order->save();

                $hold = $order->hold()->lockForUpdate()->first();

                if ($hold && $hold->status !== 'expired') {
                    $hold->status = 'cancelled';
                    $hold->save();

                    $product = $hold->product()->lockForUpdate()->first();
                    $product->stock += $hold->qty; // release stock
                    $product->save();
                }
            }

            $webhook->status = 'handled';
            $webhook->save();

            return response()->json(['message' => 'Webhook processed'], 200);
        }, 5);
    }
}