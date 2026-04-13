<?php

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Repositories\UserRepository;

final class EloquentUserRepository implements UserRepository
{
    public function __construct(
        protected readonly User $model
    ) {
    }

    public function create(array $attributes): User
    {
        return $this->model->create($attributes);
    }

    public function findByEmail(string $email): ?User
    {
        return $this->model->where('email', $email)->first();
    }

    public function findById(int $id): ?User
    {
        return $this->model->find($id);
    }

    public function findByGoogleId(string $googleId): ?User
    {
        return $this->model->where('google_id', $googleId)->first();
    }

    public function update(int $id, array $attributes): User
    {
        $user = $this->model->findOrFail($id);
        $user->update($attributes);
        return $user;
    }

    public function findByProviderId(string $provider, string $providerId): ?User
    {
        $column = $provider . '_id'; // 'google_id', 'facebook_id', ...

        return $this->model->where($column, $providerId)->first();
    }
}
