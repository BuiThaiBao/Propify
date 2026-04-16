<?php

namespace App\Events\Chat;

use App\Http\Resources\MessageResource;
use App\Models\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event broadcast real-time khi có message mới.
 *
 * Implements ShouldBroadcast (queue-based via Redis) — không block HTTP request.
 * Để dev nhanh, bạn có thể đổi sang ShouldBroadcastNow.
 *
 * Channel: private-conversation.{conversationId}
 * Authorization: routes/channels.php
 */
final class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Message $message,
    ) {
    }

    /**
     * Private channel — chỉ 2 participant mới có thể subscribe.
     *
     * @return array<PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('conversation.' . $this->message->conversation_id),
        ];
    }

    /**
     * Tên event trên client: "App\\Events\\Chat\\MessageSent" → đổi thành gọn hơn.
     */
    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    /**
     * Payload gửi về frontend.
     * Dùng MessageResource để đảm bảo format nhất quán với REST API.
     */
    public function broadcastWith(): array
    {
        $this->message->loadMissing('sender:id,full_name,avatar_url');

        return [
            'message' => (new MessageResource($this->message))->resolve(),
        ];
    }
}
