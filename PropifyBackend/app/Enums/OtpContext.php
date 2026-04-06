<?php

namespace App\Enums;

enum OtpContext: string
{
    case REGISTER       = 'register';
    case RESET_PASSWORD = 'reset';
}
