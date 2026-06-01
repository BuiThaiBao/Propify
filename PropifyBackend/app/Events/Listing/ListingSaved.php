<?php

namespace App\Events\Listing;

use App\Models\Listing;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class ListingSaved
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Listing $listing,
        public readonly ?User $user,
        public readonly string $action,
    ) {}
}
