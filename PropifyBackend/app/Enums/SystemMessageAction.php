<?php

namespace App\Enums;

enum SystemMessageAction: string
{
    case GroupCreated = 'group_created';
    case MemberAdded = 'member_added';
    case MemberRemoved = 'member_removed';
    case MemberLeft = 'member_left';
    case GroupRenamed = 'group_renamed';
    case AvatarChanged = 'avatar_changed';
    case AdminPromoted = 'admin_promoted';
}
