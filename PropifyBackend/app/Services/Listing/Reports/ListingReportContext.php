<?php

namespace App\Services\Listing\Reports;

use App\Models\Listing;
use App\Models\User;

final readonly class ListingReportContext
{
    public function __construct(
        public Listing $listing,
        public User $reporter,
        public array $reasons,
        public ?string $description,
    ) {}
}
