<?php

namespace App\Commands\User;

use App\DTOs\User\UpdateProfileDto;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Log;

final class UpdateUserProfileCommand
{
    public function __construct(
        private readonly UserRepository $userRepository,
    ) {
    }

    public function execute(User $user, UpdateProfileDto $dto): User
    {
        $data = ['full_name' => $dto->fullName];

        if ($dto->phone !== null && empty($user->phone)) {
            $data['phone'] = $dto->phone;
        }

        if ($dto->avatarUrl !== null) {
            $data['avatar_url'] = $dto->avatarUrl;
        }

        $changes = $this->changedFields($user, $data);
        $updated = $this->userRepository->update($user->id, $data);

        Log::info('User profile updated', [
            'user_id' => $user->id,
            'changed_fields' => array_keys($changes),
            'changes' => $changes,
        ]);

        return $updated;
    }

    /**
     * @param array<string, mixed> $data
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
