<?php

namespace Tests\Feature;

use App\Models\Listing;
use App\Models\Package;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListingSortingTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_sort_prioritizes_higher_package_priority(): void
    {
        $owner = User::query()->create(['phone' => '0900000001']);
        $lowPackage = Package::query()->create(['name' => 'Silver', 'slug' => 'silver', 'priority' => 1, 'price' => 0]);
        $highPackage = Package::query()->create(['name' => 'Gold', 'slug' => 'gold', 'priority' => 3, 'price' => 0]);

        $lowListing = $this->createActiveListing($owner->id, 1000000, $lowPackage->id, 10, now()->subHour());
        $highListing = $this->createActiveListing($owner->id, 2000000, $highPackage->id, 10, now()->subHour());

        $response = $this->getJson('/api/v1/listings');

        $response->assertOk();
        $ids = array_map('intval', $response->json('data.*.id'));
        $this->assertSame([$highListing->id, $lowListing->id], array_slice($ids, 0, 2));
    }

    public function test_sort_price_asc_returns_lowest_price_first(): void
    {
        $owner = User::query()->create(['phone' => '0900000002']);
        $package = Package::query()->create(['name' => 'Normal', 'slug' => 'normal', 'priority' => 1, 'price' => 0]);

        $highPrice = $this->createActiveListing($owner->id, 3000000, $package->id, 10, now()->subHour());
        $lowPrice = $this->createActiveListing($owner->id, 1000000, $package->id, 10, now()->subHour());

        $response = $this->getJson('/api/v1/listings?sort=price_asc');

        $response->assertOk();
        $ids = array_map('intval', $response->json('data.*.id'));
        $this->assertSame([$lowPrice->id, $highPrice->id], array_slice($ids, 0, 2));
    }

    public function test_sort_newest_returns_latest_published_listing_first(): void
    {
        $owner = User::query()->create(['phone' => '0900000003']);
        $package = Package::query()->create(['name' => 'Normal 2', 'slug' => 'normal-2', 'priority' => 1, 'price' => 0]);

        $olderPublished = $this->createActiveListing($owner->id, 1000000, $package->id, 10, now()->subDays(2), now()->subHour());
        $newerPublished = $this->createActiveListing($owner->id, 1200000, $package->id, 10, now()->subHour(), now()->subDays(2));

        $response = $this->getJson('/api/v1/listings?sort=newest');

        $response->assertOk();
        $ids = array_map('intval', $response->json('data.*.id'));
        $this->assertSame([$newerPublished->id, $olderPublished->id], array_slice($ids, 0, 2));
    }

    public function test_map_listings_include_property_province_and_ward_names(): void
    {
        $owner = User::query()->create(['phone' => '0900000004']);
        $package = Package::query()->create(['name' => 'Normal 3', 'slug' => 'normal-3', 'priority' => 1, 'price' => 0]);

        $listing = $this->createActiveListing($owner->id, 1000000, $package->id, 10, now()->subHour(), null, [
            'province' => 'Thành phố Hồ Chí Minh',
            'ward_code' => '26734',
            'ward' => 'Phường Bến Nghé',
            'lat' => 10.7769,
            'lng' => 106.7009,
        ]);

        $response = $this->getJson('/api/v1/listings/map');

        $response->assertOk();
        $response->assertJsonPath('data.0.id', $listing->id);
        $response->assertJsonPath('data.0.province', 'Thành phố Hồ Chí Minh');
        $response->assertJsonPath('data.0.ward', 'Phường Bến Nghé');
    }

    private function createActiveListing(int $ownerId, float $price, ?int $packageId, int $score, $publishedAt, $submittedAt = null, array $propertyOverrides = []): Listing
    {
        $property = Property::query()->create(array_merge([
            'owner_id' => $ownerId,
            'type' => 'HOUSE',
            'province_code' => '01',
            'area' => 50,
            'price' => $price,
        ], $propertyOverrides));

        return Listing::query()->create([
            'property_id' => $property->id,
            'owner_id' => $ownerId,
            'demand_type' => 'SALE',
            'title' => 'Listing '.uniqid(),
            'description' => 'Test listing',
            'status' => 'ACTIVE',
            'package_id' => $packageId,
            'score' => $score,
            'submitted_at' => $submittedAt ?? $publishedAt,
            'published_at' => $publishedAt,
        ]);
    }
}
