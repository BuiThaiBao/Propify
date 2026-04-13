<?php

namespace App\Services\Auth\Impl;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Services\Auth\SocialUserAdapter;
use App\Services\Auth\UserUpsertService;
use Illuminate\Support\Facades\Log;

/**
 * Implementation xử lý upsert user từ Social login.
 *
 * Chứa toàn bộ logic tìm/tạo/liên kết tài khoản social —
 * được tách từ AuthGoogleServiceImpl để tuân thủ SRP.
 */
final class UserUpsertServiceImpl implements UserUpsertService
{
    public function __construct(
        private readonly UserRepository $userRepository,
    ) {}

    public function upsertFromSocial(SocialUserAdapter $socialUser): User
    {
        $providerField = $socialUser->getProviderName() . '_id'; // 'google_id', 'facebook_id'

        // 1) Tìm user theo provider_id (VD: google_id)
        $user = $this->userRepository->findByProviderId(
            $socialUser->getProviderName(),
            $socialUser->getProviderId()
        );

        if ($user) {
            return $user;
        }

        // 2) Tìm theo email → liên kết provider_id vào tài khoản hiện có
        $user = $this->userRepository->findByEmail($socialUser->getEmail());

        if ($user) {
            $this->userRepository->update($user->id, [
                $providerField => $socialUser->getProviderId(),
            ]);
            $user->refresh();

            Log::info('Social account linked to existing user', [
                'user_id'  => $user->id,
                'provider' => $socialUser->getProviderName(),
            ]);

            return $user;
        }

        // 3) Tạo user mới
        $user = $this->userRepository->create([
            'full_name'    => $socialUser->getName(),
            'email'        => $socialUser->getEmail(),
            $providerField => $socialUser->getProviderId(),
            'password'     => null,
            'role'         => UserRole::User->value,
            'status'       => UserStatus::Active->value,
        ]);

        Log::info('New user registered via social', [
            'user_id'  => $user->id,
            'provider' => $socialUser->getProviderName(),
        ]);

        return $user;
    }
}
