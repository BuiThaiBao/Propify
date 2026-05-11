<?php

namespace App\Services\Packages\Impl;

use App\DTOs\Packages\CreatePackageDto;
use App\Enums\ErrorCode;
use App\Models\Package;
use App\Repositories\PackageRepository;
use App\Services\Packages\PackageService;
use Illuminate\Support\Facades\DB;
use App\Exceptions\BusinessException;

class PackageServiceImpl implements PackageService
{
    public function __construct(
        private readonly PackageRepository $packageRepository,
    ){

    }
    public function getAll()
    {
        return Package::with(['pricings' => function ($q) {
            $q->where('is_active', true)->orderBy('duration_days');
        }])->active()->byPriority()->get();
    }

    public function getById(int $id): Package
    {
        $package = $this->packageRepository->findById($id);
        if (!$package) {
            throw new BusinessException(ErrorCode::PackageNotFound);
        }
        return $package;
    }

    public function create(CreatePackageDto $dto): Package
    {
        return DB::transaction(function () use ($dto) {
            $package = $this->packageRepository->findByName($dto->name);
            if ($package) {
                throw new BusinessException(ErrorCode::PackageAlreadyExists);
            }

            return $this->packageRepository->create([
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
        });
    }

    public function update(int $id, \App\DTOs\Packages\UpdatePackageDto $dto): Package
    {
        return DB::transaction(function () use ($id, $dto) {
            $package = $this->getById($id);
            
            $this->packageRepository->update($id, [
                'name' => $dto->name,
                'price' => $dto->price,
                'priority' => $dto->priority,
                'multiplier' => $dto->multiplier,
                'daily_quota' => $dto->dailyQuota,
                'decay_rate' => $dto->decayRate,
                'badge' => $dto->badge,
                'color' => $dto->color,
                'is_active' => $dto->isActive,
            ]);

            return $this->getById($id);
        });
    }

    public function delete(int $id): void
    {
        // Xóa mềm bằng cách set is_active = false
        $package = $this->getById($id);
        $this->packageRepository->update($id, ['is_active' => false]);
    }
}
