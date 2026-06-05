<?php

namespace App\Support;

use App\Enums\ListingVerificationStatus;

final class ListingVerificationStatusResolver
{
    public static function hasAnyDocuments(?string $identityCardFront, ?string $identityCardBack, array $legalDocuments): bool
    {
        return (bool) ($identityCardFront || $identityCardBack || count($legalDocuments) > 0);
    }

    public static function hasCompleteDocuments(?string $identityCardFront, ?string $identityCardBack, array $legalDocuments): bool
    {
        return (bool) ($identityCardFront && $identityCardBack && count($legalDocuments) > 0);
    }

    public static function forSubmission(
        string $demandType,
        ?string $identityCardFront,
        ?string $identityCardBack,
        array $legalDocuments,
        ?ListingVerificationStatus $currentStatus = null,
    ): ListingVerificationStatus {
        if ($demandType === 'RENT' || ! self::hasCompleteDocuments($identityCardFront, $identityCardBack, $legalDocuments)) {
            return ListingVerificationStatus::UNVERIFIED;
        }

        if ($currentStatus === ListingVerificationStatus::VERIFIED) {
            return ListingVerificationStatus::VERIFIED;
        }

        return ListingVerificationStatus::REQUESTED;
    }
}
