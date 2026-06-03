<?php

namespace App\Services\Notification\Impl;

use App\Enums\MailType;
use App\Enums\NotificationChannelType;
use App\Enums\NotificationType;
use App\Models\User;
use App\Services\Notification\Channel\NotificationChannel;
use App\Services\Notification\NotificationService;
use Illuminate\Support\Facades\Log;

final class NotificationServiceImpl implements NotificationService
{
    /**
     * @param  NotificationChannel[]  $channels
     */
    public function __construct(
        private readonly array $channels
    ) {}

    /**
     * @param  NotificationChannelType[]  $channels  Danh sách channel muốn gửi
     */
    public function send(
        User $user,
        NotificationType|MailType $type,
        array $data = [],
        array $channels = [NotificationChannelType::EMAIL]
    ): void {
        foreach ($this->channels as $channel) {
            if (! in_array($channel->name(), $channels, true)) {
                continue;
            }

            try {
                $channel->send($user, $type, $data);

                Log::info('Notification sent', [
                    'channel' => $channel->name()->value,
                    'type' => $type->value,
                    'user_id' => $user->id,
                ]);
            } catch (\Throwable $e) {
                Log::error('Notification failed', [
                    'channel' => $channel->name()->value,
                    'type' => $type->value,
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
