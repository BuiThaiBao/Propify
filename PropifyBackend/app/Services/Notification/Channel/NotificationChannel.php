<?php

namespace App\Services\Notification\Channel;

use App\Enums\NotificationType;
use App\Enums\NotificationChanelType;
use App\Models\User;

interface NotificationChannel
{
    /**
     * Tên định danh channel: email, sms, zalo
     */
    public function name(): NotificationChanelType;

    /**
     * Gửi thông báo với template và data
     */
    public function send(User $user, NotificationType $template, array $data = []): void;
}
