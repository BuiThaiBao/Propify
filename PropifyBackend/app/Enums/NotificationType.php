<?php

namespace App\Enums;

enum NotificationType: string
{
    case PACKAGE_UPGRADED = 'package_upgraded';
    case PACKAGE_EXPIRING = 'package_expiring';
    case APPOINTMENT_BOOKED = 'appointment_booked';
    case APPOINTMENT_STATUS_UPDATED = 'appointment_status_updated';
    case APPOINTMENT_EXPIRED = 'appointment_expired';
}
