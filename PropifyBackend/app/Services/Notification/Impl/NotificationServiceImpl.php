<?php

namespace App\Services\Notification\Impl;

use App\Enums\MailType;
use App\Enums\NotificationChanelType;
use App\Models\User;
use App\Services\Notification\Channel\NotificationChannel;
use App\Services\Notification\NotificationService;
use Illuminate\Support\Facades\Log;

final class NotificationServiceImpl implements NotificationService
{
    /**
     * @param NotificationChannel[] $channels
     */
    public function __construct(
        private readonly array $channels
    ) {
    }

    /**
     * @param NotificationChanelType[] $channels  Danh sách channel muốn gửi
     */
    public function send(
        User $user,
        MailType $template,
        array $data = [],
        array $channels = [NotificationChanelType::EMAIL]
    ): void {
        foreach ($this->channels as $channel) {
            // So sánh enum với enum — dùng in_array strict
            if (!in_array($channel->name(), $channels, true)) {
                continue;
            }

            try {
                $channel->send($user, $template, $data);

                Log::info('Notification sent', [
                    'channel' => $channel->name()->value,
                    'template' => $template->value,
                    'user_id' => $user->id,
                ]);
            } catch (\Throwable $e) {
                // Không để 1 channel lỗi làm fail toàn bộ
                Log::error('Notification failed', [
                    'channel' => $channel->name()->value,
                    'template' => $template->value,
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}