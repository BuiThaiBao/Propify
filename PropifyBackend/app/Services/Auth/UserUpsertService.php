<?php

namespace App\Services\Auth;

use App\Models\User;

/**
 * Service xử lý tìm kiếm / tạo / liên kết tài khoản từ Social Provider.
 *
 * Tách logic upsert ra khỏi OAuth flow (SRP).
 * AuthGoogleServiceImpl chỉ xử lý redirect + callback,
 * còn logic DB thì delegate cho service này.
 */
interface UserUpsertService
{
    /**
     * Tìm hoặc tạo user từ thông tin social provider.
     *
     * Logic:
     * 1. Tìm theo provider_id (google_id / facebook_id) → trả về ngay
     * 2. Tìm theo email → liên kết provider_id vào tài khoản hiện có
     * 3. Không tìm thấy → tạo user mới (status=Active)
     */
    public function upsertFromSocial(SocialUserAdapter $socialUser): User;
}
