<?php

namespace App\Repositories;

use App\Models\User;

interface UserRepository
{
    /**
     * Create a new user record.
     *
     * @param array<string, mixed> $attributes
     */
    public function create(array $attributes): User;

    /**
     * Find a user by their email address.
     */
    public function findByEmail(string $email): ?User;

    /**
     * Find a user by their ID.
     */
    public function findById(int $id): ?User;

    /**
     * Find a user by their Google ID.
     */
    public function findByGoogleId(string $googleId): ?User;
}
