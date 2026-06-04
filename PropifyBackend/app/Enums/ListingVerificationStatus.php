<?php

namespace App\Enums;

enum ListingVerificationStatus: string
{
    case UNVERIFIED = 'UNVERIFIED';
    case REQUESTED = 'REQUESTED';
    case VERIFIED = 'VERIFIED';
    case REJECTED = 'REJECTED';
}
