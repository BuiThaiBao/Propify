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
    public function getAll(?string $keyword = null, ?string $status = null, bool $includeInactive = false)
    {
        return Package::query()
            ->with(['pricings' => function ($q) {
                $q->orderBy('duration_days');
            }])
            ->withCount([
                'listings',
                'transactions',
            ])
            ->when(!$includeInactive, function ($query) {
                $query->active();
            })
            ->when($status === 'active', function ($query) {
                $query->where('is_active', true);
            })
            ->when($status === 'locked', function ($query) {
                $query->where('is_active', false);
            })
            ->when($keyword, function ($query) use ($keyword) {
                $query->where(function ($subQuery) use ($keyword) {
                    $subQuery
                        ->where('name', 'like', '%' . $keyword . '%')
                        ->orWhere('slug', 'like', '%' . $keyword . '%')
                        ->orWhere('badge', 'like', '%' . $keyword . '%');
                });
            })
            ->byPriority()
            ->get();
    }

    public function getById(int $id): Package
    {
        $package = $this->packageRepository->findById($id);
        if (!$package) {
            throw new BusinessException(ErrorCode::PackageNotFound);
        }
        $package->load('pricings');
        return $package;
    }

    public function create(CreatePackageDto $dto): Package
    {
        return DB::transaction(function () use ($dto) {
            $package = $this->packageRepository->findByName($dto->name);
            if ($package) {
                throw new BusinessException(ErrorCode::PackageAlreadyExists);
            }

            $package = $this->packageRepository->create([
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

            $this->syncPricings($package, $dto->activeDurations);
            return $package;
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

            $package = $this->getById($id);
            $this->syncPricings($package, $dto->activeDurations);
            return $package;
        });
    }

    private function syncPricings(Package $package, array $activeDurations): void
    {
        // Tắt toàn bộ pricings cũ
        \App\Models\PackagePricing::where('package_id', $package->id)->update(['is_active' => false]);

        $durations = collect($activeDurations)
            ->map(fn ($days) => (int) $days)
            ->filter(fn ($days) => $days > 0)
            ->unique()
            ->values();

        foreach ($durations as $days) {
            \App\Models\PackagePricing::updateOrCreate(
                [
                    'package_id' => $package->id,
                    'duration_days' => $days
                ],
                [
                    'price' => $package->price * $days,
                    'label' => $days . ' ngày',
                    'is_active' => true
                ]
            );
        }
    }



    public function delete(int $id): void
    {
        // Xóa mềm bằng cách set is_active = false
        $package = $this->getById($id);
        $this->packageRepository->update($id, ['is_active' => false]);
    }
}
