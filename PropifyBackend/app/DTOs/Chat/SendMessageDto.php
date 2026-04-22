<?php

namespace App\DTOs\Chat;

use App\Enums\MessageType;
use App\Http\Resources\Requests\Chat\SendMessageRequest;

/**
 * DTO cho việc gửi message.
 * Hỗ trợ text, image, và file message types.
 */
final readonly class SendMessageDto
{
    public function __construct(
        public readonly int $conversationId,
        public readonly int $senderId,
        public readonly MessageType $type,
        public readonly ?string $body = null,
        public readonly ?string $fileUrl = null,
    ) {
    }

    public static function fromRequest(SendMessageRequest $request, int $conversationId, int $senderId): self
    {
        return new self(
            conversationId: $conversationId,
            senderId: $senderId,
            type: MessageType::from($request->validated('type', 'text')),
            body: $request->validated('body'),
            fileUrl: $request->validated('file_url'),
        );
    }
}
