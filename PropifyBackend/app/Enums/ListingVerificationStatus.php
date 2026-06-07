<?php

namespace App\Enums;

enum ListingVerificationStatus: string
{
    case NOT_REQUIRED = 'NOT_REQUIRED';
    case UNVERIFIED = 'UNVERIFIED';
    case REQUESTED = 'REQUESTED';
    case VERIFIED = 'VERIFIED';
    case REJECTED = 'REJECTED';
}
