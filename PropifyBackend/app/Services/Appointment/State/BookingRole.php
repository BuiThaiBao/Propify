<?php

namespace App\Services\Appointment\State;

/**
 * Vai trò của người thực hiện thao tác hủy trên một booking.
 */
enum BookingRole
{
    case VIEWER;
    case POSTER;

    public function isViewer(): bool
    {
        return $this === self::VIEWER;
    }

    /**
     * Nhãn hiển thị dùng khi ghép note (giữ nguyên chuỗi gốc của service).
     */
    public function label(): string
    {
        return $this === self::VIEWER ? 'Khách thuê' : 'Chủ nhà';
    }
}
