<?php

namespace App\Services\Packages;

use App\DTOs\Packages\CreatePackageDto;
use App\Models\Package;
use App\Repositories\PackageRepository;

final class PackageFactory
{
    public function __construct(
        private readonly PackageRepository $repository,
    ) {}

    public function create(CreatePackageDto $dto): Package
    {
        return $this->repository->create([
            'name' => $dto->name,
            'slug' => $dto->slug,
            'price' => $dto->price,
            'priority' => $dto->priority,
            'multiplier' => $dto->multiplier,
            'daily_quota' => $dto->dailyQuota,
            'decay_rate' => $dto->decayRate,
            'badge' => $dto->badge,
            'color' => $dto->color,
            'is_active' => true,
        ]);
    }
}
