<?php

namespace App\Repositories;

use App\Models\Users;

interface UserRepository
{
    /**
     * Create a new user record.
     */
    public function create(array $attributes): Users;

    /**
     * Find a user by their email address.
     */
    public function findByEmail(string $email): ?Users;

    /**
     * Find a user by their ID.
     */
    public function findById(int $id): ?Users;
}
