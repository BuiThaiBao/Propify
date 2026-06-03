<?php

namespace Tests\Feature;

use App\Models\Listing;
use App\Models\Package;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProjectSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_property_search_text_is_built_on_create_and_update(): void
    {
        $owner = User::query()->create(['phone' => '0911000001']);

        $property = Property::query()->create([
            'owner_id' => $owner->id,
            'type' => 'HOUSE',
            'province_code' => '01',
            'province' => 'Thành phố Hà Nội',
            'ward_code' => '00001',
            'ward' => 'Phường Hoàn Kiếm',
            'street_code' => 'Hàng Bài',
            'project_name' => 'Vinhomes Riverside',
            'address_detail' => '  Số 12  Phố Huế ',
            'area' => 60,
            'price' => 5000000000,
        ]);

        $this->assertSame(
            'vinhomes riverside thanh pho ha noi phuong hoan kiem hang bai so 12 pho hue',
            $property->search_text,
        );

        $property->update([
            'project_name' => 'Masteri West Heights',
            'province' => 'Thành phố Hồ Chí Minh',
            'ward' => 'Phường Bến Nghé',
            'street_code' => 'Nguyễn Huệ',
            'address_detail' => '  88 Đồng Khởi ',
        ]);

        $this->assertSame(
            'masteri west heights thanh pho ho chi minh phuong ben nghe nguyen hue 88 dong khoi',
            $property->fresh()->search_text,
        );
    }

    public function test_projects_search_matches_normalized_keyword_and_returns_relevance_meta(): void
    {
        $owner = User::query()->create(['phone' => '0911000002']);
        $package = Package::query()->create(['name' => 'Gold Search', 'slug' => 'gold-search', 'priority' => 2, 'price' => 0]);

        $matchingProperty = Property::query()->create([
            'owner_id' => $owner->id,
            'type' => 'HOUSE',
            'province_code' => '79',
            'province' => 'Thành phố Hồ Chí Minh',
            'ward_code' => '26734',
            'ward' => 'Phường Bến Nghé',
            'street_code' => 'Nguyễn Huệ',
            'project_name' => 'Saigon Centre',
            'address_detail' => '88 Đồng Khởi',
            'area' => 70,
            'price' => 8000000000,
        ]);

        Listing::query()->create([
            'property_id' => $matchingProperty->id,
            'owner_id' => $owner->id,
            'demand_type' => 'SALE',
            'title' => 'Listing search 1',
            'description' => 'Searchable property',
            'status' => 'ACTIVE',
            'package_id' => $package->id,
            'score' => 50,
            'submitted_at' => now(),
            'published_at' => now(),
        ]);

        $nonMatchingProperty = Property::query()->create([
            'owner_id' => $owner->id,
            'type' => 'HOUSE',
            'province_code' => '01',
            'province' => 'Thành phố Hà Nội',
            'ward_code' => '00001',
            'ward' => 'Phường Hàng Bạc',
            'street_code' => 'Hàng Bạc',
            'project_name' => 'Old Quarter Residence',
            'address_detail' => '1 Hàng Bạc',
            'area' => 55,
            'price' => 4000000000,
        ]);

        Listing::query()->create([
            'property_id' => $nonMatchingProperty->id,
            'owner_id' => $owner->id,
            'demand_type' => 'SALE',
            'title' => 'Listing search 2',
            'description' => 'Another property',
            'status' => 'ACTIVE',
            'package_id' => $package->id,
            'score' => 40,
            'submitted_at' => now(),
            'published_at' => now(),
        ]);

        $response = $this->getJson('/api/v1/projects/search?keyword=dong%20khoi&page=1&size=5');

        $response->assertOk();
        $response->assertJsonPath('meta.current_page', 1);
        $response->assertJsonPath('meta.per_page', 5);
        $response->assertJsonPath('data.0.id', $matchingProperty->id);
        $response->assertJsonPath('data.0.project_name', 'Saigon Centre');
        $response->assertJsonPath('data.0.active_listings_count', 1);
        $this->assertGreaterThan(0, (float) $response->json('data.0.relevance'));
    }

    public function test_projects_search_uses_like_fallback_for_short_keywords(): void
    {
        $owner = User::query()->create(['phone' => '0911000003']);
        $package = Package::query()->create(['name' => 'Silver Search', 'slug' => 'silver-search', 'priority' => 1, 'price' => 0]);

        $property = Property::query()->create([
            'owner_id' => $owner->id,
            'type' => 'HOUSE',
            'province_code' => '01',
            'province' => 'Thành phố Hà Nội',
            'ward_code' => '00001',
            'ward' => 'Phường Hoàn Kiếm',
            'street_code' => 'Lý Thái Tổ',
            'project_name' => 'Ha Tower',
            'address_detail' => '12 Lý Thái Tổ',
            'area' => 65,
            'price' => 3000000000,
        ]);

        Listing::query()->create([
            'property_id' => $property->id,
            'owner_id' => $owner->id,
            'demand_type' => 'SALE',
            'title' => 'Short keyword listing',
            'description' => 'Short keyword property',
            'status' => 'ACTIVE',
            'package_id' => $package->id,
            'score' => 35,
            'submitted_at' => now(),
            'published_at' => now(),
        ]);

        $response = $this->getJson('/api/v1/projects/search?keyword=ha');

        $response->assertOk();
        $ids = array_map('intval', $response->json('data.*.id'));
        $this->assertContains($property->id, $ids);
    }

    public function test_backfill_command_populates_province_and_ward_names_before_searching(): void
    {
        Http::fake([
            'https://provinces.open-api.vn/api/v2/p/' => Http::response([
                ['code' => '79', 'name' => 'Thành phố Hồ Chí Minh'],
            ]),
            'https://provinces.open-api.vn/api/v2/w/*' => Http::response([
                ['code' => '26734', 'name' => 'Phường Bến Nghé'],
            ]),
        ]);

        $owner = User::query()->create(['phone' => '0911000004']);
        $package = Package::query()->create(['name' => 'Platinum Search', 'slug' => 'platinum-search', 'priority' => 4, 'price' => 0]);

        $property = Property::query()->create([
            'owner_id' => $owner->id,
            'type' => 'HOUSE',
            'province_code' => '79',
            'province' => null,
            'ward_code' => '26734',
            'ward' => null,
            'street_code' => 'Nguyễn Huệ',
            'project_name' => 'Central Tower',
            'address_detail' => '88 Đồng Khởi',
            'area' => 70,
            'price' => 9000000000,
        ]);

        Listing::query()->create([
            'property_id' => $property->id,
            'owner_id' => $owner->id,
            'demand_type' => 'SALE',
            'title' => 'Backfill listing',
            'description' => 'Backfill property',
            'status' => 'ACTIVE',
            'package_id' => $package->id,
            'score' => 60,
            'submitted_at' => now(),
            'published_at' => now(),
        ]);

        $this->artisan('properties:backfill-address-fields')->assertExitCode(0);

        $property = $property->fresh();

        $this->assertSame('Thành phố Hồ Chí Minh', $property->province);
        $this->assertSame('Phường Bến Nghé', $property->ward);
        $this->assertStringContainsString('thanh pho ho chi minh', $property->search_text);
        $this->assertStringContainsString('phuong ben nghe', $property->search_text);

        $response = $this->getJson('/api/v1/projects/search?keyword=ben nghe&page=1&size=5');

        $response->assertOk();
        $ids = array_map('intval', $response->json('data.*.id'));
        $this->assertContains($property->id, $ids);
    }
}
