<?php

namespace App\DTOs\Packages;

use App\Http\Requests\Package\UpdatePackageRequest;

final readonly class UpdatePackageDto
{
    public function __construct(
        public string $name,
        public string $slug,
        public float $price,
        public int $priority,
        public float $multiplier,
        public int $dailyQuota,
        public float $decayRate,
        public ?string $badge,
        public ?string $color,
        public bool $isActive,
        public array $activeDurations = [],
    ) {
    }

    public static function fromRequest(UpdatePackageRequest $request): self
    {
        return new self(
            name: $request->input('name'),
            slug: $request->input('slug'),
            price: (float) $request->input('price'),
            priority: (int) $request->input('priority'),
            multiplier: (float) $request->input('multiplier'),
            dailyQuota: (int) $request->input('daily_quota'),
            decayRate: (float) $request->input('decay_rate'),
            badge: $request->input('badge'),
            color: $request->input('color'),
            isActive: (bool) $request->boolean('is_active'),
            activeDurations: $request->input('active_durations', [])
        );
    }
}
