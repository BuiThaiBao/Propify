<?php

namespace App\Events\Package;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class PackageStatusChanged
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly int $packageId,
        public readonly bool $isActive
    ) {}
}
