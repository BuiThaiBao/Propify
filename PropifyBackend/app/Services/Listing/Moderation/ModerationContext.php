<?php

namespace App\Services\Listing\Moderation;

/**
 * Dữ liệu cho một thao tác kiểm duyệt tin đăng của admin (duyệt/từ chối/khóa).
 */
final class ModerationContext
{
    public function __construct(
        public readonly int $adminUserId,
        public readonly ?string $reason = null,
    ) {}
}
