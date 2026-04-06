<?php

namespace App\Enums;

enum MailType: string
{
    case WELCOME = 'welcome';
    case PASSWORD_RESET = 'password_reset';
    case VERIFY_EMAIL = 'verify_email';
}