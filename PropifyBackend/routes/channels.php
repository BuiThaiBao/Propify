<?php

use App\Models\ConversationParticipant;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Channel Authorization cho private channels của Reverb.
| Client subscribe channel "private-conversation.{id}" → Laravel Echo
| tự động gọi endpoint /broadcasting/auth với JWT token.
|
*/

/**
 * private-conversation.{conversationId}
 *
 * Chỉ participant thực sự của conversation mới được subscribe.
 * Đây là bảo vệ authorization đúng nghĩa — không chỉ authenticated mà còn đúng conversation.
 */
Broadcast::channel('conversation.{conversationId}', function (User $user, int $conversationId): bool {
    return ConversationParticipant::where('conversation_id', $conversationId)
        ->where('user_id', $user->id)
        ->exists();
});
