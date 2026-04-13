<?php

namespace App\Services\Auth;

/**
 * Adapter interface cho dữ liệu user từ Social Provider (Google, Facebook, ...).
 *
 * Mục đích: tách business logic khỏi SDK bên thứ 3 (Socialite).
 * Khi thêm provider mới → chỉ tạo Adapter class mới, không sửa service cũ.
 */
interface SocialUserAdapter
{
    /**
     * Tên provider: 'google', 'facebook', 'github', ...
     */
    public function getProviderName(): string;

    /**
     * ID duy nhất của user bên phía provider.
     */
    public function getProviderId(): string;

    /**
     * Tên hiển thị của user.
     */
    public function getName(): string;

    /**
     * Email của user.
     */
    public function getEmail(): string;

    /**
     * URL avatar (nullable nếu provider không cung cấp).
     */
    public function getAvatarUrl(): ?string;
}
