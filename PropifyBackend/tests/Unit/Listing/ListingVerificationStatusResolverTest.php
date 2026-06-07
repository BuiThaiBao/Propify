<?php

namespace Tests\Unit\Listing;

use App\Enums\ListingVerificationStatus;
use App\Support\ListingVerificationStatusResolver;
use PHPUnit\Framework\TestCase;

final class ListingVerificationStatusResolverTest extends TestCase
{
    public function test_rent_listing_does_not_require_verification(): void
    {
        $status = ListingVerificationStatusResolver::forSubmission(
            'RENT',
            'https://example.com/front.jpg',
            'https://example.com/back.jpg',
            ['https://example.com/legal.jpg'],
        );

        $this->assertSame(ListingVerificationStatus::NOT_REQUIRED, $status);
    }

    public function test_sale_listing_without_documents_is_unverified(): void
    {
        $status = ListingVerificationStatusResolver::forSubmission('SALE', null, null, []);

        $this->assertSame(ListingVerificationStatus::UNVERIFIED, $status);
    }

    public function test_sale_listing_with_complete_documents_is_requested(): void
    {
        $status = ListingVerificationStatusResolver::forSubmission(
            'SALE',
            'https://example.com/front.jpg',
            'https://example.com/back.jpg',
            ['https://example.com/legal.jpg'],
        );

        $this->assertSame(ListingVerificationStatus::REQUESTED, $status);
    }

    public function test_verified_sale_listing_stays_verified_when_documents_remain_complete(): void
    {
        $status = ListingVerificationStatusResolver::forSubmission(
            'SALE',
            'https://example.com/front.jpg',
            'https://example.com/back.jpg',
            ['https://example.com/legal.jpg'],
            ListingVerificationStatus::VERIFIED,
        );

        $this->assertSame(ListingVerificationStatus::VERIFIED, $status);
    }
}
