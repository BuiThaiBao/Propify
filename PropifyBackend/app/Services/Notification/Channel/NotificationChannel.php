<?php

namespace App\Services\Notification\Channel;

use App\Enums\MailType;
use App\Enums\NotificationChannelType;
use App\Enums\NotificationType;
use App\Models\User;

interface NotificationChannel
{
    public function name(): NotificationChannelType;

    public function send(User $user, NotificationType|MailType $type, array $data = []): void;
}
