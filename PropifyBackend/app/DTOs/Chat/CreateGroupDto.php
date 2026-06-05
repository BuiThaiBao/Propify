<?php

namespace App\DTOs\Chat;

use App\Http\Requests\Chat\CreateGroupRequest;

final readonly class CreateGroupDto
{
    public function __construct(
        public int $creatorId,
        public string $name,
        public array $memberIds,
        public ?string $avatarUrl = null,
    ) {}

    public static function fromRequest(CreateGroupRequest $request, int $creatorId): self
    {
        return new self(
            creatorId: $creatorId,
            name: $request->validated('name'),
            memberIds: $request->validated('member_ids'),
            avatarUrl: $request->validated('avatar_url'),
        );
    }
}
