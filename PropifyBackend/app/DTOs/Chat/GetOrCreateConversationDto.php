<?php

namespace App\DTOs\Chat;

use App\Http\Requests\Chat\GetOrCreateConversationRequest;

/**
 * DTO để lấy hoặc tạo conversation giữa 2 user.
 * Service KHÔNG nhận FormRequest trực tiếp — clean separation.
 */
final readonly class GetOrCreateConversationDto
{
    public function __construct(
        public readonly int $currentUserId,
        public readonly int $otherUserId,
        public readonly ?int $listingId = null,
    ) {
    }

    public static function fromRequest(GetOrCreateConversationRequest $request, int $currentUserId): self
    {
        return new self(
            currentUserId: $currentUserId,
            otherUserId: $request->validated('other_user_id'),
            listingId: $request->validated('listing_id'),
        );
    }
}
