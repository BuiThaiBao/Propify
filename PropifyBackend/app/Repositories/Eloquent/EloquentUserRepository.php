<?php

namespace App\Repositories\Eloquent;

use App\Models\Users;
use App\Repositories\UserRepository;

class EloquentUserRepository implements UserRepository
{
    public function __construct(
        protected readonly Users $model
    ) {}

    public function create(array $attributes): Users
    {
        return $this->model->create($attributes);
    }

    public function findByEmail(string $email): ?Users
    {
        return $this->model->where('email', $email)->first();
    }

    public function findById(int $id): ?Users
    {
        return $this->model->find($id);
    }

    public function findByGoogleId(string $googleId): ?Users
    {
        return $this->model->where('google_id', $googleId)->first();
    }
}
