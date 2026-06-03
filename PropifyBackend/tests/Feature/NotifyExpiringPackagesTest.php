<?php

namespace Tests\Feature;

use App\Enums\NotificationType;
use App\Events\Listing\ListingPackageExpiring;
use App\Models\Listing;
use App\Models\Notification;
use App\Models\Package;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

final class NotifyExpiringPackagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_dispatches_event_for_eligible_listing(): void
    {
        Event::fake([ListingPackageExpiring::class]);

        [$owner, $listing] = $this->createExpiringListing();

        $this->artisan('packages:notify-expiring')->assertSuccessful();

        Event::assertDispatched(ListingPackageExpiring::class, function (ListingPackageExpiring $event) use ($owner, $listing) {
            return $event->user->is($owner) && $event->listing->is($listing);
        });
    }

    public function test_command_skips_duplicate_notification_for_same_expiry_timestamp(): void
    {
        Event::fake([ListingPackageExpiring::class]);

        [$owner, $listing] = $this->createExpiringListing();

        Notification::query()->create([
            'user_id' => $owner->id,
            'type' => NotificationType::PACKAGE_EXPIRING->value,
            'title' => 'Gói tin sắp hết hạn',
            'content' => 'Đã gửi',
            'data' => [
                'listing_id' => $listing->id,
                'package_expires_at' => $listing->package_expires_at->toDateTimeString(),
            ],
        ]);

        $this->artisan('packages:notify-expiring')->assertSuccessful();

        Event::assertNotDispatched(ListingPackageExpiring::class);
    }

    private function createExpiringListing(): array
    {
        $owner = User::query()->create([
            'phone' => '0911111111',
            'email' => 'owner@example.com',
        ]);

        $package = Package::query()->create([
            'name' => 'VIP',
            'slug' => 'vip',
            'priority' => 1,
            'price' => 100000,
        ]);

        $property = Property::query()->create([
            'owner_id' => $owner->id,
            'type' => 'HOUSE',
            'province_code' => '01',
            'area' => 50,
            'price' => 1000000000,
            'poster_type' => 'OWNER',
        ]);

        $listing = Listing::query()->create([
            'property_id' => $property->id,
            'owner_id' => $owner->id,
            'demand_type' => 'SALE',
            'title' => 'Listing expiring',
            'description' => 'Test listing',
            'status' => 'ACTIVE',
            'package_id' => $package->id,
            'score' => 10,
            'published_at' => now()->subHour(),
            'package_expires_at' => now()->addDays(3),
        ]);

        return [$owner, $listing];
    }
}
