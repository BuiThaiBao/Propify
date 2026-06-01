<?php

namespace App\Services\Listing\Commands;

use App\DTOs\Listing\CreateListingDto;
use App\Models\Listing;
use App\Models\User;

final class SaveDraftListingCommand
{
    public function __construct(
        private readonly CreateListingCommand $createListingCommand,
        private readonly UpdateListingCommand $updateListingCommand,
    ) {}

    public function create(User $user, CreateListingDto $dto): Listing
    {
        return $this->createListingCommand->handle($user, $dto);
    }

    public function update(User $user, int $id, CreateListingDto $dto): Listing
    {
        return $this->updateListingCommand->handle($user, $id, $dto);
    }
}
