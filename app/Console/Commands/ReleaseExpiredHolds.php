<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Hold;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReleaseExpiredHolds extends Command
{
    protected $signature = 'holds:check-expiry';

    protected $description = 'Release stock from expired holds';

    public function handle()
    {
        $expiredHolds = Hold::where('status', 'active')
            ->where('expires_at', '<', Carbon::now())
            ->get();

        foreach ($expiredHolds as $hold) {
            DB::transaction(function () use ($hold) {
                $hold->update(['status' => 'expired']);

                $product = Product::lockForUpdate()->find($hold->product_id);
                if ($product) {
                    $newReserved = max(0, $product->reserved - $hold->qty);
                    $product->update(['reserved' => $newReserved]);
                }
            });

            $this->info("Hold {$hold->id} expired. Stock released.");
        }
    }
}
