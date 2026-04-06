<?php

namespace App\Enums;

enum NotificationChanelType: string
{
    case EMAIL = 'email';
    case SMS = 'sms';
    case ZALO = 'zalo';
}