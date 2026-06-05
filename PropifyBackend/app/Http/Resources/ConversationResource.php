<?php

namespace App\Http\Resources;

use App\Enums\ConversationType;
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
        $isGroup = $this->type === ConversationType::Group;

        $otherUser = null;
        if (! $isGroup) {
            $otherUser = $this->participant_a_id === $currentUserId
                ? $this->participantB
                : $this->participantA;
        }

        $lastMessage = $this->latestMessage;

        $myParticipant = $this->conversationParticipants
            ->where('user_id', $currentUserId)
            ->first();
        $unreadCount = 0;
        if ($myParticipant && $lastMessage) {
            $lastRead = $myParticipant->last_read_at;
            if ($lastRead === null || $lastMessage->created_at > $lastRead) {
                $unreadCount = 1; // simplified — full count qua /conversations/{id}/unread
            }
        }

        return [
            'id' => $this->id,
            'type' => $this->type?->value ?? 'private',
            'listing_id' => $this->listing_id,
            'other_user' => $otherUser ? [
                'id' => $otherUser->id,
                'full_name' => $otherUser->full_name,
                'avatar_url' => $otherUser->avatar_url,
            ] : null,
            'group' => $isGroup ? [
                'name' => $this->name,
                'avatar_url' => $this->avatar_url,
                'creator_id' => $this->creator_id,
                'member_count' => $this->conversationParticipants->count(),
                'my_role' => $myParticipant?->role?->value ?? 'member',
            ] : null,
            'last_message' => $lastMessage ? [
                'body' => $lastMessage->body,
                'type' => $lastMessage->type?->value ?? $lastMessage->type,
                'sender_id' => $lastMessage->sender_id,
                'sender_name' => $lastMessage->sender?->full_name,
                'created_at' => $lastMessage->created_at?->toIso8601String(),
                'metadata' => $lastMessage->metadata,
            ] : null,
            'unread_count' => $unreadCount,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
