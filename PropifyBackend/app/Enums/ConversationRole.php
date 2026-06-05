<?php

namespace App\Enums;

enum ConversationRole: string
{
    case Admin = 'admin';
    case Member = 'member';
}
