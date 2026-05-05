<?php

namespace Database\Seeders;

use App\Models\Listing;
use App\Models\Property;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ListingSeeder extends Seeder
{
    /**
     * Seed 100 tin mua bán (SALE) cho user_id 1, 3, 4 để test ranking.
     */
    public function run(): void
    {
        $userIds = [1, 3, 4];

        // ========== Dữ liệu mẫu thực tế TP.HCM ==========

        $propertyTypes = ['APARTMENT', 'HOUSE', 'LAND', 'OFFICE', 'ROOM'];

        $directions = ['EAST', 'WEST', 'SOUTH', 'NORTH', 'NORTHEAST', 'NORTHWEST', 'SOUTHEAST', 'SOUTHWEST'];

        $furnitureStatuses = ['NONE', 'BASIC', 'FULL'];

        $posterTypes = ['OWNER', 'BROKER'];

        // Quận/Huyện TP.HCM — mã hành chính 2025
        $districts = [
            ['province' => '79', 'district' => '760', 'ward' => '26734', 'name' => 'Quận 1'],
            ['province' => '79', 'district' => '769', 'ward' => '27127', 'name' => 'Quận 3'],
            ['province' => '79', 'district' => '773', 'ward' => '27259', 'name' => 'Quận 7'],
            ['province' => '79', 'district' => '771', 'ward' => '27199', 'name' => 'Quận Bình Thạnh'],
            ['province' => '79', 'district' => '774', 'ward' => '27289', 'name' => 'Quận Tân Bình'],
            ['province' => '79', 'district' => '775', 'ward' => '27319', 'name' => 'Quận Tân Phú'],
            ['province' => '79', 'district' => '761', 'ward' => '26740', 'name' => 'Thủ Đức'],
            ['province' => '79', 'district' => '778', 'ward' => '27415', 'name' => 'Quận Gò Vấp'],
            ['province' => '79', 'district' => '772', 'ward' => '27229', 'name' => 'Quận Phú Nhuận'],
            ['province' => '79', 'district' => '776', 'ward' => '27349', 'name' => 'Quận Bình Tân'],
        ];

        $streets = [
            'Nguyễn Huệ', 'Lê Lợi', 'Nguyễn Trãi', 'Trần Hưng Đạo', 'Hai Bà Trưng',
            'Điện Biên Phủ', 'Võ Văn Tần', 'Pasteur', 'Nam Kỳ Khởi Nghĩa', 'Cách Mạng Tháng 8',
            'Lý Tự Trọng', 'Nguyễn Thị Minh Khai', 'Trường Chinh', 'Cộng Hòa', 'Hoàng Văn Thụ',
            'Phan Xích Long', 'Nguyễn Văn Trỗi', 'Lê Văn Sỹ', 'Nguyễn Đình Chiểu', 'Võ Thị Sáu',
        ];

        $projectNames = [
            'Vinhomes Central Park', 'Masteri Thảo Điền', 'Sunrise City', 'The Sun Avenue',
            'Saigon Pearl', 'Landmark 81', 'River Gate', 'Millennium', 'Kingdom 101',
            'Hado Centrosa Garden', 'Richstar', 'Moonlight Boulevard', 'Cityland Park Hills',
            'Sky Garden', 'Phú Mỹ Hưng', 'Celadon City', 'Orchard Garden',
            'Gold View', 'Novaland', 'Eco Green Saigon',
        ];

        // GPS tọa độ trung tâm các quận HCM
        $gpsCoords = [
            ['lat' => 10.7769, 'lng' => 106.7009],  // Q1
            ['lat' => 10.7832, 'lng' => 106.6910],  // Q3
            ['lat' => 10.7340, 'lng' => 106.7220],  // Q7
            ['lat' => 10.8105, 'lng' => 106.7091],  // Bình Thạnh
            ['lat' => 10.8015, 'lng' => 106.6528],  // Tân Bình
            ['lat' => 10.7946, 'lng' => 106.6286],  // Tân Phú
            ['lat' => 10.8512, 'lng' => 106.7537],  // Thủ Đức
            ['lat' => 10.8326, 'lng' => 106.6657],  // Gò Vấp
            ['lat' => 10.7989, 'lng' => 106.6827],  // Phú Nhuận
            ['lat' => 10.7652, 'lng' => 106.6040],  // Bình Tân
        ];

        $amenities = [
            'swimming_pool', 'gym', 'parking', 'elevator', 'security_24h',
            'playground', 'garden', 'bbq_area', 'tennis_court', 'sauna',
        ];

        $legalPaperTypes = ['so_do', 'so_hong', 'giay_to_hop_le', 'hop_dong_mua_ban'];

        // ========== Tiêu đề tin mẫu ==========

        $titleTemplates = [
            'Bán {type} {area}m² tại {street}, {district}',
            'Cần bán gấp {type} {bedrooms}PN {area}m² - {project}',
            '{type} cao cấp {area}m² view sông, {district}',
            'Chính chủ bán {type} {bedrooms}PN {bathrooms}WC, {street}',
            'Bán {type} {area}m², giá tốt nhất khu vực {district}',
            '{type} {project} - {bedrooms} phòng ngủ, full nội thất',
            'Cơ hội đầu tư: {type} {area}m² mặt tiền {street}',
            'Bán {type} mới 100% tại {project}, {district}',
            '{type} giá rẻ {area}m², gần trung tâm {district}',
            'Hot! {type} {bedrooms}PN view đẹp, {project}',
        ];

        $descriptionTemplates = [
            'Bất động sản được xây dựng với chất lượng cao, thiết kế hiện đại, phù hợp cho gia đình hoặc đầu tư sinh lời. Vị trí đắc địa tại {street}, {district}, gần các tiện ích công cộng như trường học, bệnh viện, siêu thị.',
            'Căn {type} nằm trong khu dân cư yên tĩnh, an ninh tốt. Diện tích sử dụng {area}m² với {bedrooms} phòng ngủ rộng rãi. Nội thất đầy đủ, sẵn sàng dọn vào ở ngay. Liên hệ để xem nhà.',
            'Vị trí thuận tiện di chuyển, kết nối dễ dàng với trung tâm thành phố. {type} được thiết kế thoáng mát, đón gió tự nhiên. Pháp lý rõ ràng, sổ hồng chính chủ.',
            'Dự án {project} - một trong những dự án đẳng cấp nhất khu vực {district}. Tiện ích nội khu: hồ bơi, gym, công viên, an ninh 24/7. Giá bán hấp dẫn, hỗ trợ vay ngân hàng lên đến 70%.',
            '{type} {area}m² tại {street}, {district}. Mặt tiền đường lớn, phù hợp kinh doanh hoặc cho thuê. Khu vực đông đúc, tiềm năng phát triển cao. Cam kết giá tốt nhất thị trường.',
        ];

        $contactNames = [
            'Nguyễn Văn An', 'Trần Thị Bình', 'Lê Hoàng Cường', 'Phạm Minh Đức',
            'Hoàng Thị Hoa', 'Vũ Quang Huy', 'Đặng Thanh Lan', 'Bùi Anh Minh',
            'Ngô Thị Ngọc', 'Dương Văn Phong',
        ];

        // ========== Tạo 100 tin ==========

        $this->command->info('🏗️  Bắt đầu seed 100 tin mua bán...');

        DB::beginTransaction();

        try {
            for ($i = 0; $i < 100; $i++) {
                // Round-robin user: 1, 3, 4
                $userId = $userIds[$i % 3];

                // Random property data
                $districtIdx = $i % count($districts);
                $district = $districts[$districtIdx];
                $gps = $gpsCoords[$districtIdx];
                $type = $propertyTypes[array_rand($propertyTypes)];
                $street = $streets[array_rand($streets)];
                $project = $projectNames[array_rand($projectNames)];
                $area = rand(25, 500);
                $bedrooms = $type === 'LAND' ? 0 : rand(1, 5);
                $bathrooms = $type === 'LAND' ? 0 : rand(1, 3);
                $price = $this->generatePrice($type, $area);
                $contactName = $contactNames[array_rand($contactNames)];

                // Tạo property
                $property = Property::create([
                    'type'                   => $type,
                    'province_code'          => $district['province'],
                    'ward_code'              => $district['ward'],
                    'street_code'            => $street,
                    'project_name'           => rand(0, 1) ? $project : null,
                    'address_detail'         => "Số " . rand(1, 300) . " " . $street,
                    'area'                   => $area,
                    'price'                  => $price,
                    'is_negotiable'          => (bool) rand(0, 1),
                    'bedrooms'               => $bedrooms,
                    'bathrooms'              => $bathrooms,
                    'floors'                 => in_array($type, ['HOUSE', 'OFFICE']) ? rand(1, 5) : null,
                    'floor_number'           => $type === 'APARTMENT' ? rand(1, 30) : null,
                    'balconies'              => rand(0, 2),
                    'facade_width'           => $type !== 'APARTMENT' ? round(rand(30, 120) / 10, 2) : null,
                    'depth'                  => $type !== 'APARTMENT' ? round(rand(100, 300) / 10, 2) : null,
                    'road_width'             => round(rand(30, 200) / 10, 2),
                    'direction_code'         => $directions[array_rand($directions)],
                    'balcony_direction_code'  => $directions[array_rand($directions)],
                    'furniture_status'       => $furnitureStatuses[array_rand($furnitureStatuses)],
                    'contact_name'           => $contactName,
                    'contact_phone'          => '09' . rand(10000000, 99999999),
                    'contact_email'          => strtolower(str_replace(' ', '.', $this->removeVietnameseAccents($contactName))) . '@gmail.com',
                    'poster_type'            => $posterTypes[array_rand($posterTypes)],
                    'amenities'              => $this->randomSubset($amenities, rand(2, 6)),
                    'legal_paper_types'      => $this->randomSubset($legalPaperTypes, rand(1, 2)),
                    'public_info_agreed'     => true,
                    'lat'                    => $gps['lat'] + (rand(-500, 500) / 100000),
                    'lng'                    => $gps['lng'] + (rand(-500, 500) / 100000),
                ]);

                // Tạo listing (tin mua bán)
                $typeVi = $this->typeToVietnamese($type);
                $districtName = $district['name'];

                $title = str_replace(
                    ['{type}', '{area}', '{street}', '{district}', '{bedrooms}', '{bathrooms}', '{project}'],
                    [$typeVi, $area, $street, $districtName, $bedrooms, $bathrooms, $project],
                    $titleTemplates[array_rand($titleTemplates)]
                );

                $descTemplate = $descriptionTemplates[array_rand($descriptionTemplates)];
                $description = str_replace(
                    ['{type}', '{area}', '{street}', '{district}', '{bedrooms}', '{project}'],
                    [$typeVi, $area, $street, $districtName, $bedrooms, $project],
                    $descTemplate
                );

                // Đa dạng status và thời gian publish
                $status = $this->weightedRandom([
                    'ACTIVE'  => 70,
                    'PENDING' => 15,
                    'DRAFT'   => 10,
                    'EXPIRED' => 5,
                ]);

                $publishedAt = null;
                $submittedAt = null;
                $expiredAt = null;

                if ($status === 'ACTIVE') {
                    // Rải đều trong 7 ngày gần đây
                    $hoursAgo = rand(0, 168);
                    $publishedAt = Carbon::now()->subHours($hoursAgo);
                    $submittedAt = (clone $publishedAt)->subHours(rand(1, 24));
                } elseif ($status === 'PENDING') {
                    $submittedAt = Carbon::now()->subHours(rand(0, 48));
                } elseif ($status === 'EXPIRED') {
                    $publishedAt = Carbon::now()->subDays(rand(30, 60));
                    $submittedAt = (clone $publishedAt)->subDays(1);
                    $expiredAt = Carbon::now()->subDays(rand(1, 7));
                }

                // Verified random (30% cơ hội verified)
                $isVerified = rand(1, 100) <= 30;

                Listing::create([
                    'property_id'   => $property->id,
                    'owner_id'      => $userId,
                    'demand_type'   => 'SALE',
                    'title'         => $title,
                    'description'   => $description,
                    'ai_description' => rand(0, 1) ? "Bất động sản $typeVi tại $districtName với diện tích $area m². Phù hợp cho gia đình hoặc đầu tư. Khu vực an ninh, giao thông thuận tiện." : null,
                    'status'        => $status,
                    'package_id'    => null, // Normal — chưa mua gói
                    'score'         => 0,
                    'is_verified'   => $isVerified,
                    'has_video'     => (bool) rand(0, 1),
                    'request_verification' => !$isVerified && rand(0, 1),
                    'submitted_at'  => $submittedAt,
                    'published_at'  => $publishedAt,
                    'expired_at'    => $expiredAt,
                ]);

                // Progress bar
                if (($i + 1) % 25 === 0) {
                    $this->command->info("  ✅ Đã tạo " . ($i + 1) . "/100 tin");
                }
            }

            DB::commit();
            $this->command->info('🎉 Seed 100 tin mua bán thành công!');
            $this->command->info('   → User 1: 34 tin | User 3: 33 tin | User 4: 33 tin');

        } catch (\Throwable $e) {
            DB::rollBack();
            $this->command->error('❌ Seed thất bại: ' . $e->getMessage());
            throw $e;
        }
    }

    // ==================== Helper Methods ====================

    /**
     * Tạo giá BĐS hợp lý theo loại & diện tích (VNĐ).
     */
    private function generatePrice(string $type, int $area): float
    {
        $pricePerM2 = match ($type) {
            'APARTMENT' => rand(30, 80) * 1_000_000,   // 30-80 triệu/m²
            'HOUSE'     => rand(50, 150) * 1_000_000,   // 50-150 triệu/m²
            'LAND'      => rand(20, 100) * 1_000_000,   // 20-100 triệu/m²
            'OFFICE'    => rand(40, 120) * 1_000_000,   // 40-120 triệu/m²
            'ROOM'      => rand(10, 30) * 1_000_000,    // 10-30 triệu/m²
            default     => rand(30, 80) * 1_000_000,
        };

        return $area * $pricePerM2;
    }

    /**
     * Chuyển loại BĐS sang tiếng Việt cho title.
     */
    private function typeToVietnamese(string $type): string
    {
        return match ($type) {
            'APARTMENT' => 'căn hộ',
            'HOUSE'     => 'nhà phố',
            'LAND'      => 'đất nền',
            'OFFICE'    => 'văn phòng',
            'ROOM'      => 'phòng trọ',
            default     => 'bất động sản',
        };
    }

    /**
     * Random theo trọng số (weighted random).
     */
    private function weightedRandom(array $items): string
    {
        $total = array_sum($items);
        $rand = rand(1, $total);
        $cumulative = 0;

        foreach ($items as $item => $weight) {
            $cumulative += $weight;
            if ($rand <= $cumulative) {
                return $item;
            }
        }

        return array_key_first($items);
    }

    /**
     * Lấy subset ngẫu nhiên từ array.
     */
    private function randomSubset(array $items, int $count): array
    {
        $keys = array_rand($items, min($count, count($items)));
        if (!is_array($keys)) {
            $keys = [$keys];
        }
        return array_values(array_intersect_key($items, array_flip($keys)));
    }

    /**
     * Xóa dấu tiếng Việt (cho email).
     */
    private function removeVietnameseAccents(string $str): string
    {
        $map = [
            'à' => 'a', 'á' => 'a', 'ả' => 'a', 'ã' => 'a', 'ạ' => 'a',
            'ă' => 'a', 'ằ' => 'a', 'ắ' => 'a', 'ẳ' => 'a', 'ẵ' => 'a', 'ặ' => 'a',
            'â' => 'a', 'ầ' => 'a', 'ấ' => 'a', 'ẩ' => 'a', 'ẫ' => 'a', 'ậ' => 'a',
            'đ' => 'd',
            'è' => 'e', 'é' => 'e', 'ẻ' => 'e', 'ẽ' => 'e', 'ẹ' => 'e',
            'ê' => 'e', 'ề' => 'e', 'ế' => 'e', 'ể' => 'e', 'ễ' => 'e', 'ệ' => 'e',
            'ì' => 'i', 'í' => 'i', 'ỉ' => 'i', 'ĩ' => 'i', 'ị' => 'i',
            'ò' => 'o', 'ó' => 'o', 'ỏ' => 'o', 'õ' => 'o', 'ọ' => 'o',
            'ô' => 'o', 'ồ' => 'o', 'ố' => 'o', 'ổ' => 'o', 'ỗ' => 'o', 'ộ' => 'o',
            'ơ' => 'o', 'ờ' => 'o', 'ớ' => 'o', 'ở' => 'o', 'ỡ' => 'o', 'ợ' => 'o',
            'ù' => 'u', 'ú' => 'u', 'ủ' => 'u', 'ũ' => 'u', 'ụ' => 'u',
            'ư' => 'u', 'ừ' => 'u', 'ứ' => 'u', 'ử' => 'u', 'ữ' => 'u', 'ự' => 'u',
            'ỳ' => 'y', 'ý' => 'y', 'ỷ' => 'y', 'ỹ' => 'y', 'ỵ' => 'y',
            'À' => 'A', 'Á' => 'A', 'Ả' => 'A', 'Ã' => 'A', 'Ạ' => 'A',
            'Ă' => 'A', 'Ằ' => 'A', 'Ắ' => 'A', 'Ẳ' => 'A', 'Ẵ' => 'A', 'Ặ' => 'A',
            'Â' => 'A', 'Ầ' => 'A', 'Ấ' => 'A', 'Ẩ' => 'A', 'Ẫ' => 'A', 'Ậ' => 'A',
            'Đ' => 'D',
            'È' => 'E', 'É' => 'E', 'Ẻ' => 'E', 'Ẽ' => 'E', 'Ẹ' => 'E',
            'Ê' => 'E', 'Ề' => 'E', 'Ế' => 'E', 'Ể' => 'E', 'Ễ' => 'E', 'Ệ' => 'E',
            'Ì' => 'I', 'Í' => 'I', 'Ỉ' => 'I', 'Ĩ' => 'I', 'Ị' => 'I',
            'Ò' => 'O', 'Ó' => 'O', 'Ỏ' => 'O', 'Õ' => 'O', 'Ọ' => 'O',
            'Ô' => 'O', 'Ồ' => 'O', 'Ố' => 'O', 'Ổ' => 'O', 'Ỗ' => 'O', 'Ộ' => 'O',
            'Ơ' => 'O', 'Ờ' => 'O', 'Ớ' => 'O', 'Ở' => 'O', 'Ỡ' => 'O', 'Ợ' => 'O',
            'Ù' => 'U', 'Ú' => 'U', 'Ủ' => 'U', 'Ũ' => 'U', 'Ụ' => 'U',
            'Ư' => 'U', 'Ừ' => 'U', 'Ứ' => 'U', 'Ử' => 'U', 'Ữ' => 'U', 'Ự' => 'U',
            'Ỳ' => 'Y', 'Ý' => 'Y', 'Ỷ' => 'Y', 'Ỹ' => 'Y', 'Ỵ' => 'Y',
        ];

        return strtr($str, $map);
    }
}
