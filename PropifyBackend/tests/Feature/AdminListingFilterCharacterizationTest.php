<?php

namespace Tests\Feature;

use App\Models\Listing;
use App\Models\Package;
use App\Models\Property;
use App\Models\User;
use App\Repositories\ListingRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Characterization test: ghi lại HÀNH VI HIỆN TẠI của lọc/tìm kiếm admin
 * (repository), để bảo vệ khi refactor. Không khẳng định hành vi đúng/sai —
 * chỉ chốt những gì code đang làm.
 */
final class AdminListingFilterCharacterizationTest extends TestCase
{
    use RefreshDatabase;

    private function repo(): ListingRepository
    {
        return app(ListingRepository::class);
    }

    private function seedListings(): array
    {
        $owners = [];
        $make = function (string $status, string $demand, string $title, string $ownerName, float $price) use (&$owners) {
            static $i = 0;
            $i++;
            $owner = User::query()->create([
                'full_name' => $ownerName,
                'phone' => '09010000'.str_pad((string) $i, 2, '0', STR_PAD_LEFT),
                'email' => "owner{$i}@example.com",
            ]);
            $property = Property::query()->create([
                'owner_id' => $owner->id,
                'type' => 'HOUSE',
                'province_code' => '01',
                'area' => 50,
                'price' => $price,
                'poster_type' => 'OWNER',
                'address_detail' => 'Quan '.$i,
            ]);

            return Listing::query()->create([
                'property_id' => $property->id,
                'owner_id' => $owner->id,
                'demand_type' => $demand,
                'title' => $title,
                'description' => 'desc',
                'status' => $status,
                'score' => 10,
            ]);
        };

        return [
            'active' => $make('ACTIVE', 'SALE', 'Biet thu cao cap', 'Nguyen Van A', 800000000),
            'pending' => $make('PENDING', 'RENT', 'Can ho mini', 'Tran Thi B', 2000000000),
            'rejected' => $make('REJECTED', 'SALE', 'Nha pho mat tien', 'Le Van C', 6000000000),
            'locked' => $make('LOCKED', 'RENT', 'Phong tro sinh vien', 'Pham Thi D', 500000000),
            'draft' => $make('DRAFT', 'SALE', 'Tin nhap', 'Hoang E', 1000000000),
            'unlisted' => $make('UNLISTED', 'SALE', 'Tin go', 'Vo F', 1000000000),
        ];
    }

    public function test_status_counts_exclude_draft_and_unlisted(): void
    {
        $this->seedListings();

        $counts = $this->repo()->getAdminStatusCounts(null, null);

        $this->assertSame(4, $counts['all']);
        $this->assertSame(1, $counts['pending']);
        $this->assertSame(1, $counts['approved']);
        $this->assertSame(1, $counts['rejected']);
        $this->assertSame(1, $counts['locked']);
    }

    public function test_paginate_filter_by_status(): void
    {
        $l = $this->seedListings();

        $result = $this->repo()->paginateAdmin('PENDING', null, null, 20);

        $this->assertSame(1, $result->total());
        $this->assertSame($l['pending']->id, $result->items()[0]->id);
    }

    public function test_paginate_search_by_title(): void
    {
        $l = $this->seedListings();

        $result = $this->repo()->paginateAdmin('all', null, 'Can ho', 20, 'title');

        $ids = collect($result->items())->pluck('id')->all();
        $this->assertContains($l['pending']->id, $ids);
    }

    public function test_paginate_search_by_owner_is_consistent_with_counts(): void
    {
        $l = $this->seedListings();

        // Sau refactor: paginate & count dùng CHUNG bộ filter → tìm theo owner
        // trả kết quả nhất quán (trước đây paginate bị trả rỗng do áp keyword 2 lần).
        $result = $this->repo()->paginateAdmin('all', null, 'Tran', 20, 'owner');
        $counts = $this->repo()->getAdminStatusCounts(null, 'Tran', 'owner');

        $ids = collect($result->items())->pluck('id')->all();

        $this->assertSame(1, $result->total());
        $this->assertContains($l['pending']->id, $ids);
        $this->assertSame(1, $counts['all']);
    }

    public function test_paginate_search_by_address(): void
    {
        $l = $this->seedListings();

        // 'Quan 2' là address_detail của listing pending (seed theo thứ tự tạo).
        $result = $this->repo()->paginateAdmin('all', null, 'Quan 2', 20, 'address');

        $ids = collect($result->items())->pluck('id')->all();

        $this->assertContains($l['pending']->id, $ids);
        $this->assertNotContains($l['active']->id, $ids);
    }
}
