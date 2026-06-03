<?php

namespace App\Services\Notification;

use App\Enums\MailType;
use App\Enums\NotificationChannelType;
use App\Enums\NotificationType;
use App\Models\User;

interface NotificationService
{
    /**
     * Gửi thông báo tới user qua một hoặc nhiều channel.
     *
     * @param  NotificationChannelType[]  $channels
     */
    public function send(
        User $user,
        NotificationType|MailType $type,
        array $data = [],
        array $channels = [NotificationChannelType::EMAIL]
    ): void;
}
