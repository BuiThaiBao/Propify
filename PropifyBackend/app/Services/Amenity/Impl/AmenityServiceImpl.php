<?php

namespace App\Services\Amenity\Impl;

use App\DTOs\Amenity\CreateAmenityDto;
use App\DTOs\Amenity\UpdateAmenityDto;
use App\Enums\ErrorCode;
use App\Exceptions\BusinessException;
use App\Models\Attribute;
use App\Repositories\AmenityRepository;
use App\Services\Amenity\AmenityService;
use Illuminate\Database\Eloquent\Collection;

final class AmenityServiceImpl implements AmenityService
{
    public function __construct(
        private readonly AmenityRepository $amenityRepository,
    ) {}

    public function getAll(): Collection
    {
        return $this->amenityRepository->all();
    }

    public function create(CreateAmenityDto $dto): Attribute
    {
        $group = $this->amenityRepository->getAmenityGroup();

        return $this->amenityRepository->create([
            'group_id' => $group->id,
            'name' => $dto->name,
            'icon' => $dto->icon,
            'order_index' => $dto->orderIndex,
        ]);
    }

    public function update(int $id, UpdateAmenityDto $dto): Attribute
    {
        $amenity = $this->amenityRepository->findAmenityById($id);

        if (! $amenity) {
            throw new BusinessException(ErrorCode::ResourceNotFound);
        }

        return $this->amenityRepository->update($amenity, [
            'name' => $dto->name,
            'icon' => $dto->icon,
            'order_index' => $dto->orderIndex,
        ]);
    }
}
