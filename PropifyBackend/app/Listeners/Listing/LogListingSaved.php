<?php

namespace App\Listeners\Listing;

use App\Events\Listing\ListingSaved;
use Illuminate\Support\Facades\Log;

final class LogListingSaved
{
    public function handle(ListingSaved $event): void
    {
        Log::info('[Listing] Listing saved', [
            'listing_id' => $event->listing->id,
            'user_id' => $event->user?->id,
            'action' => $event->action,
            'status' => $event->listing->status,
        ]);
    }
}
