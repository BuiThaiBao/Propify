<?php

namespace App\Commands\User;

use App\DTOs\User\ChangePasswordDto;
use App\Events\Auth\PasswordChanged;
use App\Models\AuditLog;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Services\User\Validation\PasswordValidationChain;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

final class ChangeUserPasswordCommand
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly PasswordValidationChain $passwordValidationChain,
    ) {
    }

    public function execute(User $user, ChangePasswordDto $dto): void
    {
        $this->passwordValidationChain->validate($user, $dto);

        $this->userRepository->update($user->id, [
            'password' => Hash::make($dto->newPassword),
        ]);

        AuditLog::create([
            'actor_id' => $user->id,
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'action' => 'user.password.changed',
            'changes' => [],
            'metadata' => [
                'changed_fields' => ['password'],
            ],
        ]);

        Log::info('User password changed', [
            'user_id' => $user->id,
        ]);
        event(new PasswordChanged($user->id));
    }
}
