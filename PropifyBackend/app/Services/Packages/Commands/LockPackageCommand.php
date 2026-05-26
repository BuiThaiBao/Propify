<?php

namespace App\Services\Packages\Commands;

use App\Enums\ErrorCode;
use App\Events\Package\PackageStatusChanged;
use App\Exceptions\BusinessException;
use App\Models\Package;
use App\Repositories\PackageRepository;

final class LockPackageCommand
{
    public function __construct(
        private readonly PackageRepository $repository,
    ) {}

    public function execute(int $packageId): Package
    {
        $package = $this->repository->findById($packageId);
        if (!$package) {
            throw new BusinessException(ErrorCode::PackageNotFound);
        }
        if (!$package->is_active) {
            throw new BusinessException(ErrorCode::BadRequest, 'Gói đã ở trạng thái khóa.');
        }

        $this->repository->update($packageId, ['is_active' => false]);
        $updated = $this->repository->findById($packageId);
        event(new PackageStatusChanged($packageId, false));

        return $updated;
    }
}
