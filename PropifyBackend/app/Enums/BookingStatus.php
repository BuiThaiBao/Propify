<?php

namespace App\Enums;

enum BookingStatus: string
{
    case PENDING = 'PENDING';
    case APPROVED = 'APPROVED';
    case COMPLETED = 'COMPLETED';
    case CANCELLED_BY_VIEWER = 'CANCELLED_BY_VIEWER';
    case CANCELLED_BY_POSTER = 'CANCELLED_BY_POSTER';
    case EXPIRED = 'EXPIRED';
}
