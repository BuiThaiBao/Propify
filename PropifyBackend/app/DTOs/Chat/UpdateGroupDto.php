<?php

namespace App\DTOs\Chat;

use App\Http\Requests\Chat\UpdateGroupRequest;

final readonly class UpdateGroupDto
{
    public function __construct(
        public int $conversationId,
        public int $userId,
        public ?string $name = null,
        public ?string $avatarUrl = null,
    ) {}

    public static function fromRequest(UpdateGroupRequest $request, int $conversationId, int $userId): self
    {
        return new self(
            conversationId: $conversationId,
            userId: $userId,
            name: $request->validated('name'),
            avatarUrl: $request->validated('avatar_url'),
        );
    }
}
