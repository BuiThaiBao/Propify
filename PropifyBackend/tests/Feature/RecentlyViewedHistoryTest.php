<?php

namespace Tests\Feature;

use App\Models\Listing;
use App\Models\Package;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class RecentlyViewedHistoryTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private int $packageId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::query()->create(['phone' => '0901234567']);
        $package = Package::query()->create([
            'name' => 'Normal',
            'slug' => 'normal',
            'priority' => 1,
            'price' => 0,
        ]);
        $this->packageId = $package->id;
    }

    public function test_authenticated_user_recently_viewed_listings_flow(): void
    {
        $listing1 = $this->createListing();
        $listing2 = $this->createListing();

        // 1. Initial list should be empty
        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/v1/recently-viewed');
        $response->assertOk();
        $response->assertJsonCount(0, 'data');

        // 2. Track view for listing 1 (actingAs user)
        $viewResponse = $this->actingAs($this->user, 'api')
            ->postJson("/api/v1/listings/{$listing1->id}/view");
        $viewResponse->assertOk();

        sleep(1);

        // 3. Track view for listing 2
        $viewResponse = $this->actingAs($this->user, 'api')
            ->postJson("/api/v1/listings/{$listing2->id}/view");
        $viewResponse->assertOk();

        // 4. Retrieve list: should have listing 2 first, listing 1 second (sorted by latest update)
        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/v1/recently-viewed');
        $response->assertOk();
        $response->assertJsonCount(2, 'data');
        $this->assertEquals($listing2->id, $response->json('data.0.id'));
        $this->assertEquals($listing1->id, $response->json('data.1.id'));

        // 5. Re-visit listing 1 (touch/update timestamp)
        // Wait a second to ensure different timestamp if database resolution is low,
        // but sleep is generally not preferred unless needed. Let's just touch or fake delay
        // by manually updating the updated_at or running view track again.
        // Actually, since Eloquent updates the timestamp during the trackView call,
        // it will touch updated_at to now().
        sleep(1);
        $viewResponse = $this->actingAs($this->user, 'api')
            ->postJson("/api/v1/listings/{$listing1->id}/view");
        $viewResponse->assertOk();

        // 6. Retrieve list again: listing 1 must now be at the top!
        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/v1/recently-viewed');
        $response->assertOk();
        $response->assertJsonCount(2, 'data');
        $this->assertEquals($listing1->id, $response->json('data.0.id'));
        $this->assertEquals($listing2->id, $response->json('data.1.id'));

        // 7. Deactivate listing 1
        $listing1->update(['status' => 'DRAFT']);

        // 8. Retrieve list: should only return listing 2 since listing 1 is no longer ACTIVE
        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/v1/recently-viewed');
        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $this->assertEquals($listing2->id, $response->json('data.0.id'));
    }

    private function createListing(): Listing
    {
        $property = Property::query()->create([
            'owner_id' => $this->user->id,
            'type' => 'HOUSE',
            'province_code' => '01',
            'area' => 50,
            'price' => 1000000000,
            'poster_type' => 'OWNER',
        ]);

        return Listing::query()->create([
            'property_id' => $property->id,
            'owner_id' => $this->user->id,
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
