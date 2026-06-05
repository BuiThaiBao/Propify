<?php

namespace App\Models;

use App\Enums\ConversationType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

final class Conversation extends Model
{
    protected $table = 'conversations';

    protected $fillable = [
        'type',
        'name',
        'avatar_url',
        'creator_id',
        'participant_a_id', // min(user_a, user_b) — normalized
        'participant_b_id', // max(user_a, user_b) — normalized
        'listing_id',
    ];

    protected $casts = [
        'type' => ConversationType::class,
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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'conversation_id');
    }

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'conversation_participants', 'conversation_id', 'user_id')
            ->withPivot(['role', 'nickname', 'joined_at', 'last_read_at', 'last_seen_at'])
            ->withTimestamps();
    }

    public function conversationParticipants(): HasMany
    {
        return $this->hasMany(ConversationParticipant::class, 'conversation_id');
    }

    /**
     * Latest message — dùng latestOfMany() để eager load hiệu quả.
     * SQL: SELECT * FROM messages WHERE id IN (SELECT MAX(id) FROM messages GROUP BY conversation_id)
     */
    public function latestMessage(): HasOne
    {
        return $this->hasOne(Message::class, 'conversation_id')->latestOfMany();
    }

    // ==================== Helpers ====================

    /**
     * Trả về thông tin participant còn lại (không phải current user).
     */
    public function getOtherParticipant(int $currentUserId): ?User
    {
        if ($this->isGroup()) {
            return null;
        }

        if ($this->participant_a_id === $currentUserId) {
            return $this->participantB;
        }

        return $this->participantA;
    }

    public function isGroup(): bool
    {
        return $this->type === ConversationType::Group;
    }

    public function isPrivate(): bool
    {
        return ! $this->isGroup();
    }
}
