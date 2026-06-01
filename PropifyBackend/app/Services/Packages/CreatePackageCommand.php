<?php

namespace App\Services\Packages;

use App\DTOs\Packages\CreatePackageDto;
use App\Enums\ErrorCode;
use App\Events\Package\PackageCreated;
use App\Exceptions\BusinessException;
use App\Models\Package;
use App\Models\PackagePricing;
use App\Repositories\PackageRepository;
use Illuminate\Support\Facades\DB;

final class CreatePackageCommand
{
    public function __construct(
        private readonly PackageFactory $factory,
        private readonly PackageRepository $repository,
    ) {}

    public function execute(CreatePackageDto $dto): Package
    {
        return DB::transaction(function () use ($dto) {
            if ($this->repository->findByName($dto->name)) {
                throw new BusinessException(ErrorCode::PackageAlreadyExists);
            }

            $package = $this->factory->create($dto);
            $this->syncPricings($package, $dto->activeDurations);
            event(new PackageCreated($package->id));

            return $package;
        });
    }

    private function syncPricings(Package $package, array $activeDurations): void
    {
        PackagePricing::where('package_id', $package->id)->update(['is_active' => false]);
        $durations = collect($activeDurations)->map(fn ($d) => (int) $d)->filter(fn ($d) => $d > 0)->unique()->values();
        foreach ($durations as $days) {
            PackagePricing::updateOrCreate(
                ['package_id' => $package->id, 'duration_days' => $days],
                ['price' => $package->price * $days, 'label' => $days.' ngày', 'is_active' => true]
            );
        }
    }
}
