<?php

namespace Tests\Unit\Listing;

use App\Exceptions\BusinessException;
use App\Services\Listing\State\ListingStatusStateFactory;
use PHPUnit\Framework\TestCase;

final class ListingUnlistStateTest extends TestCase
{
    public function test_active_listing_can_transition_to_unlisted(): void
    {
        $factory = new ListingStatusStateFactory();

        $this->assertTrue($factory->make('ACTIVE')->canTransitionTo('UNLISTED'));
    }

    public function test_non_active_listing_cannot_transition_to_unlisted(): void
    {
        $factory = new ListingStatusStateFactory();

        $this->assertFalse($factory->make('DRAFT')->canTransitionTo('UNLISTED'));
        $this->assertFalse($factory->make('PENDING')->canTransitionTo('UNLISTED'));
        $this->assertFalse($factory->make('REJECTED')->canTransitionTo('UNLISTED'));
        $this->assertFalse($factory->make('LOCKED')->canTransitionTo('UNLISTED'));
    }

    public function test_unlisted_listing_has_no_next_transition(): void
    {
        $factory = new ListingStatusStateFactory();

        $this->assertFalse($factory->make('UNLISTED')->canTransitionTo('ACTIVE'));
        $this->assertFalse($factory->make('UNLISTED')->canTransitionTo('LOCKED'));
    }

    public function test_factory_rejects_unknown_status_with_custom_message(): void
    {
        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('Trang thai listing UNKNOWN khong hop le.');

        (new ListingStatusStateFactory())->make('UNKNOWN');
    }
}
