<?php

namespace App\Repositories;



use App\Models\Package;

interface PackageRepository
{


    public function all();
    public function findById(int $id): ?Package;
    public function findByName(string $name): ?Package;
    public function create(array $attributes): Package;
    public function update(int $id, array $attributes): bool;
    public function delete(int $id): bool;
}
