<?php

namespace App\Enums;

enum UserStatus: string
{
    case Active  = 'A';
    case Pending = 'P';    // Chờ xác thực OTP
    case Inactive = 'IA';
    case Banned  = 'BAN';
}
