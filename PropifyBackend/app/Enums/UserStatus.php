<?php

namespace App\Enums;

enum UserStatus: string
{
    case Active = 'A';
    case Inactive = 'IA';
    case Banned = 'BAN';
}
