<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Product;
use App\Models\Hold;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class FlashSaleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Product::factory()->create([
            'id' => 1,
            'name' => 'iPhone 15',
            'price' => 1000.00,
            'stock' => 10,
            'reserved' => 0
        ]);
    }

    public function test_get_product_returns_correct_data()
    {
        $response = $this->getJson('/api/products/1');

        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'name',
                'price',
                'stock'
            ]);
    }

    public function test_it_prevents_overselling_when_stock_is_fully_reserved()
    {
        $this->postJson('/api/holds', [
            'product_id' => 1,
            'qty' => 10
        ])->assertStatus(201);

        $this->assertDatabaseHas('products', ['id' => 1, 'reserved' => 10]);

        $response2 = $this->postJson('/api/holds', [
            'product_id' => 1,
            'qty' => 1
        ]);

        
        if ($response2->status() === 400) {
            $response2->assertStatus(400);
        } else {
            $response2->assertStatus(409);
        }

        $this->assertEquals(10, Product::find(1)->reserved);
    }

    public function test_expired_holds_release_reserved_stock()
    {
        $this->postJson('/api/holds', ['product_id' => 1, 'qty' => 5]);

        Carbon::setTestNow(now()->addMinutes(5));

        $this->artisan('holds:check-expiry');

        $this->assertDatabaseHas('holds', [
            'product_id' => 1,
            'qty' => 5,
            'status' => 'expired'
        ]);

        $this->assertEquals(0, Product::find(1)->reserved);
    }

    public function test_webhook_is_idempotent()
    {
        $hold = Hold::create([
            'product_id' => 1,
            'qty' => 1,
            'expires_at' => now()->addMinutes(10),
            'status' => 'active'
        ]);

        $order = Order::create([
            'hold_id' => $hold->id,
            'total' => 1000,
            'status' => 'pre_payment'
        ]);

        $payload = [
            'idempotency_key' => 'unique_payment_ref_123',
            'type' => 'payment.success',
            'order_id' => $order->id,  
            'status' => 'success',     
            'payload' => [             
                'amount' => 1000
            ]
        ];

        $this->postJson('/api/payments/webhook', $payload)->assertStatus(200);

        $this->assertEquals('paid', $order->fresh()->status);
        $this->assertDatabaseHas('webhook_events', ['idempotency_key' => 'unique_payment_ref_123']);

        $this->postJson('/api/payments/webhook', $payload)->assertStatus(200);

        $this->assertEquals('paid', $order->fresh()->status);
        $this->assertDatabaseCount('webhook_events', 1);
    }

    public function test_webhook_arrives_before_order_creation()
    {
        $payload = [
            'idempotency_key' => 'early_bird_payment',
            'type' => 'payment.success',
            'order_id' => 999, 
            'status' => 'success',
            'payload' => []
        ];

        $this->postJson('/api/payments/webhook', $payload)->assertStatus(202);

        $this->assertDatabaseHas('webhook_events', [
            'idempotency_key' => 'early_bird_payment',
            'status' => 'processing'
        ]);
    }
}
