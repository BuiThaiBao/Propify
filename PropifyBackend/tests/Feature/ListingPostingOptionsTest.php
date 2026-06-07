<?php

namespace Tests\Feature;

use Tests\TestCase;

class ListingPostingOptionsTest extends TestCase
{
    public function test_posting_options_include_supported_property_types(): void
    {
        $response = $this->getJson('/api/v1/listings/posting-options');

        $response->assertOk();

        $saleTypes = collect($response->json('data.property_types.sale'))->pluck('value');
        $rentTypes = collect($response->json('data.property_types.rent'))->pluck('value');

        $this->assertSame([
            'APARTMENT',
            'PRIVATE_HOUSE',
            'STREET_HOUSE',
            'MINI_APARTMENT',
            'VILLA_TOWNHOUSE',
            'SHOPHOUSE',
            'KIOSK',
            'OFFICE',
            'RESORT',
            'RESTAURANT_HOTEL',
        ], $saleTypes->all());

        $this->assertSame([
            'APARTMENT',
            'RENT_ROOM',
            'BOARDING_HOUSE',
            'PRIVATE_HOUSE',
            'STREET_HOUSE',
            'MINI_APARTMENT',
            'VILLA_TOWNHOUSE',
            'SHOPHOUSE',
            'KIOSK',
            'OFFICE',
            'RESORT',
            'RESTAURANT_HOTEL',
        ], $rentTypes->all());
    }

    public function test_posting_options_include_listing_verification_statuses(): void
    {
        $response = $this->getJson('/api/v1/listings/posting-options');

        $response->assertOk()
            ->assertJsonPath('data.listing_verification_statuses.0.value', 'NOT_REQUIRED')
            ->assertJsonPath('data.listing_verification_statuses.1.value', 'UNVERIFIED')
            ->assertJsonPath('data.listing_verification_statuses.2.value', 'REQUESTED')
            ->assertJsonPath('data.listing_verification_statuses.3.value', 'VERIFIED')
            ->assertJsonPath('data.listing_verification_statuses.4.value', 'REJECTED');
    }
}
