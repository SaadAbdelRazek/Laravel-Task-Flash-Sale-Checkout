<?php

namespace App\Jobs;

use App\Models\Hold;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ExpireHoldJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $holdId;

    public function __construct($holdId)
    {
        $this->holdId = $holdId;
    }

    public function handle(): void
    {
        DB::transaction(function () {

            // Lock hold row
            $hold = Hold::where('id', $this->holdId)->lockForUpdate()->first();

            if (!$hold) {
                return;
            }

            // If already used/cancelled/expired --> don't touch stock
            if ($hold->status !== 'active') {
                return;
            }

            // Ensure hold has truly expired
            if ($hold->expires_at && $hold->expires_at->isFuture()) {
                // Job fired early (rare case), skip
                return;
            }

            // Release reserved stock
            $product = $hold->product()->lockForUpdate()->first();

            if ($product) {
                $product->reserved = max(0, $product->reserved - $hold->qty);
                $product->save();
            }

            // Mark hold as expired
            $hold->status = 'expired';
            $hold->save();
        }, 5);
    }
}
