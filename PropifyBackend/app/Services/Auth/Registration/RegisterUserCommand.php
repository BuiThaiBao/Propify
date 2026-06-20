<?php

namespace App\Services\Auth\Registration;

use App\DTOs\Auth\RegisterUserDto;
use App\Enums\OtpContext;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Repositories\UserRepository;
use App\Services\Otp\OtpService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

final class RegisterUserCommand
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly RegistrationValidationChain $validationChain,
        private readonly OtpService $otpService,
    ) {
    }

    public function execute(RegisterUserDto $dto): void
    {
        $this->validationChain->validate($dto);



        DB::transaction(function () use ($dto) {
            $user = $this->userRepository->findByEmail($dto->email);

            if ($user) {
                $this->userRepository->update($user->id, [
                    'full_name' => $dto->fullName,
                    'password' => Hash::make($dto->password),
                ]);
                $user->refresh();
                Log::info('Existing pending user re-registered', ['user_id' => $user->id]);
            } else {
                $user = $this->userRepository->create([
                    'full_name' => $dto->fullName,
                    'email' => $dto->email,
                    'password' => Hash::make($dto->password),
                    'role' => UserRole::User->value,
                    'status' => UserStatus::Pending->value,
                ]);
                Log::info('New user registered (pending OTP)', ['user_id' => $user->id]);
            }

            $this->otpService->generate($user, OtpContext::REGISTER);
        });
    }
}
