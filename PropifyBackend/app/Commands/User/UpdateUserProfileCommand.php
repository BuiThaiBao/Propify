<?php

namespace App\Commands\User;

use App\DTOs\User\UpdateProfileDto;
use App\Events\User\ProfileUpdated;
use App\Models\AuditLog;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Services\User\Validation\ProfileValidationChain;
use Illuminate\Support\Facades\Log;

final class UpdateUserProfileCommand
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly ProfileValidationChain $profileValidationChain,
    ) {}

    public function execute(User $user, UpdateProfileDto $dto): User
    {
        $this->profileValidationChain->validate($dto);

        $data = ['full_name' => $dto->fullName];

        if ($dto->phone !== null && empty($user->phone)) {
            $data['phone'] = $dto->phone;
        }

        if ($dto->avatarUrl !== null) {
            $data['avatar_url'] = $dto->avatarUrl;
        }

        $changes = $this->changedFields($user, $data);
        $updated = $this->userRepository->update($user->id, $data);

        AuditLog::create([
            'actor_id' => $user->id,
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'action' => 'user.profile.updated',
            'changes' => $changes,
            'metadata' => [
                'changed_fields' => array_keys($changes),
            ],
        ]);

        Log::info('User profile updated', [
            'user_id' => $user->id,
            'changed_fields' => array_keys($changes),
            'changes' => $changes,
        ]);
        event(new ProfileUpdated($user->id));

        return $updated;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, array{old: mixed, new: mixed}>
     */
    private function changedFields(User $user, array $data): array
    {
        $changes = [];

        foreach ($data as $field => $newValue) {
            $oldValue = $user->{$field};

            if ($oldValue === $newValue) {
                continue;
            }

            $changes[$field] = [
                'old' => $oldValue,
                'new' => $newValue,
            ];
        }

        return $changes;
    }
}
