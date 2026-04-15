<?php

namespace App\Enums;

enum BookingStatus: string
{
    case PENDING   = 'PENDING';
    case APPROVED  = 'APPROVED';
    case CANCELLED = 'CANCELLED';
}
