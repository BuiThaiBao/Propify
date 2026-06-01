<?php

namespace Tests\Feature;

use App\Models\Listing;
use App\Models\Package;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListingFilteringTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private int $packageId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::query()->create(['phone' => '0901234567']);
        $package = Package::query()->create([
            'name' => 'Normal',
            'slug' => 'normal',
            'priority' => 1,
            'price' => 0,
        ]);
        $this->packageId = $package->id;
    }

    public function test_filter_by_poster_type(): void
    {
        $ownerListing = $this->createListingWithDetails('OWNER', 1000000000, 50);
        $brokerListing = $this->createListingWithDetails('BROKER', 2000000000, 60);

        // Test filter OWNER
        $response = $this->getJson('/api/v1/listings?poster_type=OWNER');
        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $this->assertEquals($ownerListing->id, $response->json('data.0.id'));

        // Test filter BROKER
        $response = $this->getJson('/api/v1/listings?poster_type=BROKER');
        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $this->assertEquals($brokerListing->id, $response->json('data.0.id'));
    }

    public function test_filter_by_price_range(): void
    {
        $listing1 = $this->createListingWithDetails('OWNER', 1000000000, 50); // 1 Billion
        $listing2 = $this->createListingWithDetails('OWNER', 3000000000, 60); // 3 Billion
        $listing3 = $this->createListingWithDetails('OWNER', 5000000000, 70); // 5 Billion

        // Test min_price = 2 Billion
        $response = $this->getJson('/api/v1/listings?min_price=2000000000');
        $response->assertOk();
        $response->assertJsonCount(2, 'data');
        $ids = $response->json('data.*.id');
        $this->assertContains($listing2->id, $ids);
        $this->assertContains($listing3->id, $ids);

        // Test max_price = 4 Billion
        $response = $this->getJson('/api/v1/listings?max_price=4000000000');
        $response->assertOk();
        $response->assertJsonCount(2, 'data');
        $ids = $response->json('data.*.id');
        $this->assertContains($listing1->id, $ids);
        $this->assertContains($listing2->id, $ids);

        // Test range [2 Billion, 4 Billion]
        $response = $this->getJson('/api/v1/listings?min_price=2000000000&max_price=4000000000');
        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $this->assertEquals($listing2->id, $response->json('data.0.id'));
    }

    public function test_filter_by_area_range(): void
    {
        $listing1 = $this->createListingWithDetails('OWNER', 1000000000, 25);
        $listing2 = $this->createListingWithDetails('OWNER', 2000000000, 45);
        $listing3 = $this->createListingWithDetails('OWNER', 3000000000, 75);

        // Test min_area = 40
        $response = $this->getJson('/api/v1/listings?min_area=40');
        $response->assertOk();
        $response->assertJsonCount(2, 'data');
        $ids = $response->json('data.*.id');
        $this->assertContains($listing2->id, $ids);
        $this->assertContains($listing3->id, $ids);

        // Test max_area = 50
        $response = $this->getJson('/api/v1/listings?max_area=50');
        $response->assertOk();
        $response->assertJsonCount(2, 'data');
        $ids = $response->json('data.*.id');
        $this->assertContains($listing1->id, $ids);
        $this->assertContains($listing2->id, $ids);

        // Test range [30, 60]
        $response = $this->getJson('/api/v1/listings?min_area=30&max_area=60');
        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $this->assertEquals($listing2->id, $response->json('data.0.id'));
    }

    public function test_combined_filters(): void
    {
        $listing1 = $this->createListingWithDetails('OWNER', 1000000000, 25); // Owner, 1B, 25m2
        $listing2 = $this->createListingWithDetails('OWNER', 2000000000, 45); // Owner, 2B, 45m2
        $listing3 = $this->createListingWithDetails('BROKER', 2000000000, 45); // Broker, 2B, 45m2

        $response = $this->getJson('/api/v1/listings?poster_type=OWNER&min_price=1500000000&min_area=30');
        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $this->assertEquals($listing2->id, $response->json('data.0.id'));
    }

    private function createListingWithDetails(string $posterType, float $price, float $area): Listing
    {
        $property = Property::query()->create([
            'owner_id' => $this->owner->id,
            'type' => 'HOUSE',
            'province_code' => '01',
            'area' => $area,
            'price' => $price,
            'poster_type' => $posterType,
        ]);

        return Listing::query()->create([
            'property_id' => $property->id,
            'owner_id' => $this->owner->id,
            'demand_type' => 'SALE',
            'title' => 'Listing '.uniqid(),
            'description' => 'Test listing',
            'status' => 'ACTIVE',
            'package_id' => $this->packageId,
            'score' => 10,
            'published_at' => now()->subHour(),
        ]);
    }
}
