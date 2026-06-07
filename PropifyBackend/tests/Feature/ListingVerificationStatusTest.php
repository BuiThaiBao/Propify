<?php

namespace Tests\Feature;

use App\Enums\ListingVerificationStatus;
use App\Models\Listing;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ListingVerificationStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_listing_verification_status_is_stored_and_returned_as_enum_text(): void
    {
        $user = User::query()->create(['phone' => '0901234567']);
        $property = Property::query()->create([
            'owner_id' => $user->id,
            'type' => 'APARTMENT',
            'province_code' => '01',
            'area' => 50,
            'price' => 1000000000,
            'poster_type' => 'OWNER',
        ]);
        $listing = Listing::query()->create([
            'property_id' => $property->id,
            'owner_id' => $user->id,
            'demand_type' => 'SALE',
            'title' => 'Tin dang can xac thuc',
            'description' => 'Mo ta tin dang co day du thong tin.',
            'status' => 'ACTIVE',
            'is_verified' => ListingVerificationStatus::REQUESTED,
        ]);

        $this->assertSame(ListingVerificationStatus::REQUESTED, $listing->fresh()->is_verified);

        $this->getJson("/api/v1/listings/{$listing->id}")
            ->assertOk()
            ->assertJsonPath('data.is_verified', 'REQUESTED');
    }

    public function test_rent_listing_is_created_with_not_required_verification_status(): void
    {
        $user = User::query()->create(['phone' => '0901234568']);

        $response = $this->actingAs($user, 'api')->postJson('/api/v1/listings', [
            'demand_type' => 'RENT',
            'title' => 'Tin cho thue khong can xac thuc',
            'description' => 'Mo ta tin cho thue co day du thong tin toi thieu.',
            'property_type' => 'APARTMENT',
            'province_code' => '01',
            'province' => 'Ha Noi',
            'ward_code' => '001',
            'ward' => 'Phuong Test',
            'street_code' => 'Duong Test',
            'address_detail' => 'So 1 Duong Test',
            'area' => 50,
            'price' => 5000000,
            'bedrooms' => 2,
            'bathrooms' => 1,
            'contact_name' => 'Nguyen Van A',
            'contact_phone' => '0901234568',
            'poster_type' => 'OWNER',
            'images' => ['https://example.com/listing.jpg'],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.is_verified', 'NOT_REQUIRED');
    }
}
