<?php

namespace App\DTOs\Chat;

use App\Http\Requests\Chat\AddGroupMembersRequest;

final readonly class GroupMemberDto
{
    public function __construct(
        public int $conversationId,
        public int $actorId,
        public array $userIds,
    ) {}

    public static function fromRequest(AddGroupMembersRequest $request, int $conversationId, int $actorId): self
    {
        return new self(
            conversationId: $conversationId,
            actorId: $actorId,
            userIds: $request->validated('user_ids'),
        );
    }
}
