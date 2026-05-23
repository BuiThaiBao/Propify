<?php

namespace App\Enums;

enum AuthMethod: string
{
    case EmailPassword = 'email_password';
    case GoogleOAuth = 'google_oauth';
}
