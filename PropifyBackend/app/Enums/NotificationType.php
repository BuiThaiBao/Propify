<?php

namespace App\Enums;

enum NotificationType: string
{
    case WELCOME = 'welcome';
    case PASSWORD_RESET = 'password_reset';
    case VERIFY_EMAIL = 'verify_email';
    case FORGOT_PASSWORD = 'forgot_password';
    case PACKAGE_UPGRADED = 'package_upgraded';
    case PACKAGE_EXPIRING = 'package_expiring';
}
