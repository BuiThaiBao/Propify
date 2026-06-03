<?php

namespace App\Enums;

enum NotificationChanelType: string
{
    case DATABASE = 'database';
    case EMAIL = 'email';
    case SMS = 'sms';
    case ZALO = 'zalo';
}
