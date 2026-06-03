<?php

namespace App\Support;

final class ListingPostingOptions
{
    public static function all(): array
    {
        return [
            'demand_types' => [
                ['value' => 'SALE', 'label' => 'Mua bán'],
                ['value' => 'RENT', 'label' => 'Cho thuê'],
            ],
            'property_types' => [
                'sale' => [
                    ['value' => 'APARTMENT', 'label' => 'Căn hộ chung cư'],
                    ['value' => 'HOUSE', 'label' => 'Nhà ở'],
                    ['value' => 'LAND', 'label' => 'Đất'],
                    ['value' => 'OFFICE', 'label' => 'Văn phòng'],
                    ['value' => 'ROOM', 'label' => 'Phòng'],
                ],
                'rent' => [
                    ['value' => 'APARTMENT', 'label' => 'Căn hộ chung cư'],
                    ['value' => 'HOUSE', 'label' => 'Nhà ở'],
                    ['value' => 'LAND', 'label' => 'Đất'],
                    ['value' => 'OFFICE', 'label' => 'Văn phòng'],
                    ['value' => 'ROOM', 'label' => 'Phòng'],
                ],
            ],
            'legal_paper_types' => [
                ['value' => 'LAND_USE_CERTIFICATE', 'label' => 'Giấy CN QSDĐ - Sổ đỏ - Sổ hồng'],
                ['value' => 'SALE_CONTRACT', 'label' => 'Hợp đồng mua bán'],
                ['value' => 'CAPITAL_CONTRIBUTION_CONTRACT', 'label' => 'Hợp đồng góp vốn'],
                ['value' => 'ALLOTTED_OR_SUBDIVIDED_LAND', 'label' => 'Đất giao - Đất phân'],
                ['value' => 'BORROWED_LAND', 'label' => 'Đất mượn'],
                ['value' => 'LEASED_LAND', 'label' => 'Đất thuê'],
                ['value' => 'ORIGIN_PROOF_DOCUMENT', 'label' => 'Giấy tờ chứng minh nguồn gốc'],
                ['value' => 'NO_LAND_CERTIFICATE', 'label' => 'Chưa làm giấy CN QSDĐ'],
                ['value' => 'PROCESSING_LAND_CERTIFICATE', 'label' => 'Đang làm giấy CN QSDĐ'],
                ['value' => 'APPOINTMENT_FOR_CERTIFICATE', 'label' => 'Đã có giấy hẹn lấy sổ'],
                ['value' => 'BUSINESS_TRANSFER', 'label' => 'Sang nhượng doanh nghiệp'],
                ['value' => 'SHARE_TRANSFER', 'label' => 'Mua bán cổ phần'],
                ['value' => 'INVESTMENT_COOPERATION', 'label' => 'Hợp tác đầu tư'],
                ['value' => 'HANDWRITTEN', 'label' => 'Viết tay'],
            ],
            'quick_numbers' => [1, 2, 3, 4, 5],
            'amenities' => [
                ['value' => 'Sân chơi', 'label' => 'Sân chơi'],
                ['value' => 'Bể bơi', 'label' => 'Bể bơi'],
                ['value' => 'Sân vườn', 'label' => 'Sân vườn'],
                ['value' => 'Thang máy', 'label' => 'Thang máy'],
                ['value' => 'Wifi', 'label' => 'Wifi'],
                ['value' => 'Khu để xe', 'label' => 'Khu để xe'],
            ],
            'directions' => [
                ['value' => 'N', 'label' => 'Bắc'],
                ['value' => 'NE', 'label' => 'Đông Bắc'],
                ['value' => 'E', 'label' => 'Đông'],
                ['value' => 'SE', 'label' => 'Đông Nam'],
                ['value' => 'S', 'label' => 'Nam'],
                ['value' => 'SW', 'label' => 'Tây Nam'],
                ['value' => 'W', 'label' => 'Tây'],
                ['value' => 'NW', 'label' => 'Tây Bắc'],
            ],
            'furniture_statuses' => [
                ['value' => 'FULL', 'label' => 'Có'],
                ['value' => 'BASIC', 'label' => 'Cơ bản'],
                ['value' => 'NONE', 'label' => 'Không'],
            ],
            'poster_types' => [
                ['value' => 'OWNER', 'label' => 'Chủ nhà'],
                ['value' => 'BROKER', 'label' => 'Môi giới'],
            ],
            'listing_statuses' => [
                ['value' => 'DRAFT', 'label' => 'Nháp'],
                ['value' => 'PENDING', 'label' => 'Chờ duyệt'],
                ['value' => 'ACTIVE', 'label' => 'Đang đăng'],
                ['value' => 'EXPIRED', 'label' => 'Hết hạn'],
                ['value' => 'REJECTED', 'label' => 'Từ chối'],
                ['value' => 'LOCKED', 'label' => 'Đã khóa'],
                ['value' => 'UNLISTED', 'label' => 'Đã gỡ'],
            ],
            'admin_listing_statuses' => [
                ['value' => 'ACTIVE', 'label' => 'Duyệt đăng'],
                ['value' => 'REJECTED', 'label' => 'Từ chối'],
                ['value' => 'LOCKED', 'label' => 'Khóa tin'],
            ],
            'verification_document_types' => [
                ['value' => 'ID_FRONT', 'label' => 'CCCD mặt trước'],
                ['value' => 'ID_BACK', 'label' => 'CCCD mặt sau'],
                ['value' => 'LEGAL_DOCUMENT', 'label' => 'Giấy tờ pháp lý'],
            ],
            'listing_video_providers' => [
                ['value' => 'LOCAL', 'label' => 'Tải lên'],
                ['value' => 'YOUTUBE', 'label' => 'YouTube'],
                ['value' => 'VIMEO', 'label' => 'Vimeo'],
            ],
            'favorite_types' => [
                ['value' => 'FAVORITE', 'label' => 'Yêu thích'],
                ['value' => 'VIEWED', 'label' => 'Đã xem'],
            ],
            'listing_report_reasons' => [
                ['value' => 'WRONG_PRICE', 'label' => 'Sai giá'],
                ['value' => 'WRONG_ADDRESS', 'label' => 'Sai địa chỉ'],
                ['value' => 'SOLD_OR_RENTED', 'label' => 'Đã bán hoặc đã cho thuê'],
                ['value' => 'WRONG_INFORMATION', 'label' => 'Thông tin không đúng'],
                ['value' => 'UNREACHABLE_OWNER', 'label' => 'Không liên hệ được chủ tin'],
                ['value' => 'DUPLICATE_LISTING', 'label' => 'Tin đăng trùng lặp'],
            ],
            'message_types' => [
                ['value' => 'text', 'label' => 'Văn bản'],
                ['value' => 'image', 'label' => 'Hình ảnh'],
                ['value' => 'file', 'label' => 'Tệp'],
            ],
            'booking_statuses' => [
                ['value' => 'PENDING', 'label' => 'Chờ xác nhận'],
                ['value' => 'APPROVED', 'label' => 'Đã duyệt'],
                ['value' => 'CANCELLED_BY_VIEWER', 'label' => 'Khách hủy'],
                ['value' => 'CANCELLED_BY_POSTER', 'label' => 'Người đăng hủy'],
                ['value' => 'EXPIRED', 'label' => 'Quá hạn'],
            ],
            'poster_booking_statuses' => [
                ['value' => 'APPROVED', 'label' => 'Duyệt lịch'],
                ['value' => 'CANCELLED_BY_POSTER', 'label' => 'Từ chối lịch'],
            ],
            'appointment_statuses' => [
                ['value' => 'PENDING', 'label' => 'Chờ xác nhận'],
                ['value' => 'CONFIRMED', 'label' => 'Đã xác nhận'],
                ['value' => 'DECLINED', 'label' => 'Đã từ chối'],
                ['value' => 'CANCELLED', 'label' => 'Đã hủy'],
                ['value' => 'COMPLETED', 'label' => 'Hoàn thành'],
            ],
            'upload_types' => [
                ['value' => 'avatar', 'label' => 'Ảnh đại diện'],
                ['value' => 'listing', 'label' => 'Tin đăng'],
            ],
            'transaction_payment_methods' => [
                ['value' => 'VNPAY', 'label' => 'VNPay'],
                ['value' => 'MOMO', 'label' => 'MoMo'],
                ['value' => 'SIMULATED', 'label' => 'Mô phỏng'],
            ],
            'transaction_statuses' => [
                ['value' => 'PENDING', 'label' => 'Chờ thanh toán'],
                ['value' => 'SUCCESS', 'label' => 'Thành công'],
                ['value' => 'FAILED', 'label' => 'Thất bại'],
                ['value' => 'EXPIRED', 'label' => 'Quá hạn'],
            ],
            'rental' => [
                'min_terms' => [
                    ['value' => '1_month', 'label' => '1 tháng'],
                    ['value' => '3_months', 'label' => '3 tháng'],
                    ['value' => '6_months', 'label' => '6 tháng'],
                    ['value' => '1_year', 'label' => '1 năm'],
                ],
                'payment_intervals' => [
                    ['value' => 'monthly', 'label' => '1 tháng/lần'],
                    ['value' => 'quarter', 'label' => '3 tháng/lần'],
                    ['value' => 'half_year', 'label' => '6 tháng/lần'],
                    ['value' => 'yearly', 'label' => '1 năm/lần'],
                ],
                'deposits' => [
                    ['value' => 'none', 'label' => 'Không'],
                    ['value' => '1_month', 'label' => '1 tháng'],
                    ['value' => '3_months', 'label' => '3 tháng'],
                    ['value' => '6_months', 'label' => '6 tháng'],
                    ['value' => '1_year', 'label' => '1 năm'],
                ],
            ],
        ];
    }

    public static function values(string $key): array
    {
        $options = self::all();

        return match ($key) {
            'demand_types' => array_column($options['demand_types'], 'value'),
            'property_types' => array_values(array_unique([
                ...array_column($options['property_types']['sale'], 'value'),
                ...array_column($options['property_types']['rent'], 'value'),
            ])),
            'legal_paper_types' => array_column($options['legal_paper_types'], 'value'),
            'amenities' => array_column($options['amenities'], 'value'),
            'directions' => array_column($options['directions'], 'value'),
            'furniture_statuses' => array_column($options['furniture_statuses'], 'value'),
            'poster_types' => array_column($options['poster_types'], 'value'),
            'listing_statuses' => array_column($options['listing_statuses'], 'value'),
            'admin_listing_statuses' => array_column($options['admin_listing_statuses'], 'value'),
            'verification_document_types' => array_column($options['verification_document_types'], 'value'),
            'listing_video_providers' => array_column($options['listing_video_providers'], 'value'),
            'favorite_types' => array_column($options['favorite_types'], 'value'),
            'listing_report_reasons' => array_column($options['listing_report_reasons'], 'value'),
            'message_types' => array_column($options['message_types'], 'value'),
            'booking_statuses' => array_column($options['booking_statuses'], 'value'),
            'poster_booking_statuses' => array_column($options['poster_booking_statuses'], 'value'),
            'appointment_statuses' => array_column($options['appointment_statuses'], 'value'),
            'upload_types' => array_column($options['upload_types'], 'value'),
            'transaction_payment_methods' => array_column($options['transaction_payment_methods'], 'value'),
            'transaction_statuses' => array_column($options['transaction_statuses'], 'value'),
            'rent_min_terms' => array_column($options['rental']['min_terms'], 'value'),
            'rent_payment_intervals' => array_column($options['rental']['payment_intervals'], 'value'),
            'rent_deposits' => array_column($options['rental']['deposits'], 'value'),
            default => [],
        };
    }

    public static function firstValue(string $key): ?string
    {
        return self::values($key)[0] ?? null;
    }
}
