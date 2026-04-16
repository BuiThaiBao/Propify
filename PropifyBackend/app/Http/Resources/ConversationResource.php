<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ConversationResource extends JsonResource
{
    /**
     * @param  int|null  $currentUserId  Dùng để xác định "other participant"
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $currentUserId = auth()->id();

        // Lấy thông tin participant còn lại (không phải current user)
        $otherUser = $this->participant_a_id === $currentUserId
            ? $this->participantB
            : $this->participantA;

        // Last message (HasOne latestOfMany — truy cập trực tiếp, không cần ->first())
        $lastMessage = $this->latestMessage;

        // Unread count từ pivot (participant của current user)
        $myParticipant = $this->conversationParticipants->first();
        $unreadCount = 0;
        if ($myParticipant && $lastMessage) {
            $lastRead = $myParticipant->last_read_at;
            if ($lastRead === null || $lastMessage->created_at > $lastRead) {
                $unreadCount = 1; // simplified — full count qua /conversations/{id}/unread
            }
        }

        return [
            'id'         => $this->id,
            'listing_id' => $this->listing_id,
            'other_user' => $otherUser ? [
                'id'         => $otherUser->id,
                'full_name'  => $otherUser->full_name,
                'avatar_url' => $otherUser->avatar_url,
            ] : null,
            'last_message' => $lastMessage ? [
                'body'       => $lastMessage->body,
                'type'       => $lastMessage->type?->value ?? $lastMessage->type,
                'created_at' => $lastMessage->created_at?->toIso8601String(),
            ] : null,
            'unread_count' => $unreadCount,
            'created_at'   => $this->created_at?->toIso8601String(),
            'updated_at'   => $this->updated_at?->toIso8601String(),
        ];
    }
}
