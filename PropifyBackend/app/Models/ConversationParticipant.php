<?php

namespace App\Models;

use App\Enums\ConversationRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ConversationParticipant extends Model
{
    protected $table = 'conversation_participants';

    protected $fillable = [
        'conversation_id',
        'user_id',
        'role',
        'nickname',
        'joined_at',
        'last_read_at',
        'last_seen_at',
    ];

    protected $casts = [
        'role' => ConversationRole::class,
        'joined_at' => 'datetime',
        'last_read_at' => 'datetime',
        'last_seen_at' => 'datetime',
    ];

    // ==================== Relationships ====================

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class, 'conversation_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function isAdmin(): bool
    {
        return $this->role === ConversationRole::Admin;
    }
}
