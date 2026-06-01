<?php

namespace App\Services\Packages;

use App\DTOs\Packages\CreatePackageDto;
use App\DTOs\Packages\UpdatePackageDto;
use App\Models\Package;

interface PackageService
{
    public function getAll(?string $keyword = null, ?string $status = null, bool $includeInactive = false);

    public function getById(int $id): Package;

    public function create(CreatePackageDto $dto): Package;

    public function update(int $id, UpdatePackageDto $dto): Package;

    public function lock(int $id): Package;

    public function activate(int $id): Package;
}
