<?php

namespace App\Services\User\Commands;

use App\Models\User;

interface UserCommandInterface
{
    /**
     * Execute the user command.
     */
    public function execute(): User;
}
