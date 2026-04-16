<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Conversation extends Model
{
    protected $table = 'conversations';

    protected $fillable = [
        'participant_a_id', // min(user_a, user_b) — normalized
        'participant_b_id', // max(user_a, user_b) — normalized
        'listing_id',
    ];

    // ==================== Relationships ====================

    public function participantA(): BelongsTo
    {
        return $this->belongsTo(User::class, 'participant_a_id');
    }

    public function participantB(): BelongsTo
    {
        return $this->belongsTo(User::class, 'participant_b_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'conversation_id');
    }

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'conversation_participants', 'conversation_id', 'user_id')
                    ->withPivot(['last_read_at', 'last_seen_at'])
                    ->withTimestamps();
    }

    public function conversationParticipants(): HasMany
    {
        return $this->hasMany(ConversationParticipant::class, 'conversation_id');
    }

    public function latestMessage(): HasMany
    {
        return $this->hasMany(Message::class, 'conversation_id')->latest()->limit(1);
    }

    // ==================== Helpers ====================

    /**
     * Trả về thông tin participant còn lại (không phải current user).
     */
    public function getOtherParticipant(int $currentUserId): ?User
    {
        if ($this->participant_a_id === $currentUserId) {
            return $this->participantB;
        }

        return $this->participantA;
    }
}
