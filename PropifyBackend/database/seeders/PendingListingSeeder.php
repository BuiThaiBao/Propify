<?php

namespace Database\Seeders;

use App\Models\Listing;
use App\Models\Property;
use App\Models\User;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PendingListingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $userIds = [1, 3, 4];

        foreach ($userIds as $id) {
            // Đảm bảo user tồn tại
            $user = User::firstOrCreate(
                ['id' => $id],
                [
                    'full_name' => "User Test $id",
                    'email' => "user$id@example.com",
                    'password' => Hash::make('password'),
                    'role' => UserRole::User,
                    'status' => UserStatus::Active,
                    'phone' => '012345678' . $id,
                ]
            );

            // Tạo 2 listing cho mỗi user
            for ($i = 1; $i <= 2; $i++) {
                $property = Property::create([
                    'type' => $i % 2 == 0 ? 'APARTMENT' : 'HOUSE',
                    'province_code' => '79', // TP.HCM
                    'ward_code' => '26743',
                    'street_code' => 'Đường số ' . $i,
                    'project_name' => 'Dự án Meyland ' . $id,
                    'address_detail' => "$i/2$id Đường số $i, Phường ABC, Quận XYZ",
                    'area' => 50 + ($id * 10) + ($i * 5),
                    'price' => 2000000000 + ($id * 500000000) + ($i * 100000000),
                    'is_negotiable' => true,
                    'bedrooms' => 2 + $i,
                    'bathrooms' => 1 + $i,
                    'floors' => $i,
                    'direction_code' => 'NORTH',
                    'contact_name' => $user->full_name,
                    'contact_phone' => $user->phone,
                    'contact_email' => $user->email,
                    'poster_type' => 'OWNER',
                    'amenities' => ['GYM', 'POOL', 'PARK'],
                    'legal_paper_types' => ['SỔ HỒNG'],
                    'lat' => 10.762622 + (rand(-100, 100) / 10000),
                    'lng' => 106.660172 + (rand(-100, 100) / 10000),
                ]);

                Listing::create([
                    'property_id' => $property->id,
                    'owner_id' => $user->id,
                    'demand_type' => $i % 2 == 0 ? 'RENT' : 'SALE',
                    'title' => ($i % 2 == 0 ? 'Cho thuê ' : 'Bán ') . ($property->type == 'APARTMENT' ? 'căn hộ ' : 'nhà phố ') . $property->project_name,
                    'description' => "Mô tả chi tiết cho tin đăng số $i của user $id. Bất động sản tọa lạc tại vị trí đắc địa, đầy đủ tiện ích xung quanh. Cần " . ($i % 2 == 0 ? 'cho thuê' : 'bán') . " gấp.",
                    'status' => 'PENDING',
                    'submitted_at' => now(),
                    'views' => 0,
                    'is_verified' => false,
                    'has_video' => false,
                    'score' => rand(0, 10),
                    'appointment_contact_name' => $user->full_name,
                    'appointment_contact_phone' => $user->phone,
                    'appointment_days' => ['MON', 'WED', 'FRI'],
                ]);
            }
        }
    }
}
