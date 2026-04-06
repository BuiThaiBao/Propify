<?php

namespace App\Services\Notification;

use App\Enums\MailType;
use App\Enums\NotificationChanelType;
use App\Models\User;

interface NotificationService
{
    /**
     * Gửi thông báo tới user qua một hoặc nhiều channel.
     *
     * @param NotificationChanelType[] $channels
     */
    public function send(
        User $user,
        MailType $template,
        array $data = [],
        array $channels = [NotificationChanelType::EMAIL]
    ): void;
}
