<?php


namespace App\Services\Packages;
use App\DTOs\Packages\CreatePackageDto;
use App\Models\Package;

interface PackageService
{
    public function getAll(?string $keyword = null, ?string $status = null, bool $includeInactive = false);
    public function getById(int $id): Package;
    public function create(CreatePackageDto $dto): Package;
    public function update(int $id, \App\DTOs\Packages\UpdatePackageDto $dto): Package;
    public function delete(int $id): void;
}
