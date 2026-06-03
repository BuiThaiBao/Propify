<?php

namespace App\Events\Notification;

use App\Models\Notification;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class NotificationSent implements ShouldBroadcastNow, ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Notification $notification
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('user.'.$this->notification->user_id)];
    }

    public function broadcastAs(): string
    {
        return 'notification.sent';
    }

    public function broadcastWith(): array
    {
        return [
            'notification' => [
                'id' => $this->notification->id,
                'user_id' => $this->notification->user_id,
                'type' => $this->notification->type,
                'title' => $this->notification->title,
                'content' => $this->notification->content,
                'data' => $this->notification->data,
                'read_at' => $this->notification->read_at?->toISOString(),
                'created_at' => $this->notification->created_at?->toISOString(),
                'updated_at' => $this->notification->updated_at?->toISOString(),
            ],
        ];
    }
}
