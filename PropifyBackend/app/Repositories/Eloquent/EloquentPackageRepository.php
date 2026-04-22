<?php

namespace App\Repositories\Eloquent;

use App\Models\Package;
use App\Repositories\PackageRepository;

final class EloquentPackageRepository implements PackageRepository
{

    public function __construct(protected readonly Package $model){

    }
    public function all()
    {
        return $this->model->all();
    }

    public function findById(int $id): ?Package
    {
        return $this->model->find($id);
    }

    public function findByName(string $name): ?Package
    {
        return $this->model->where('name', $name)->first();
    }

    public function create(array $attributes): Package
    {
        return $this->model->create($attributes);
    }

    public function update(int $id, array $attributes): bool
    {
        $package = $this->findById($id);
        if (!$package) return false;
        
        return $package->update($attributes);
    }

    public function delete(int $id): bool
    {
        $package = $this->findById($id);
        if (!$package) return false;

        return $package->delete();
    }
}
