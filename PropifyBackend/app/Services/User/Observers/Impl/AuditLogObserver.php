<?php

namespace App\Services\User\Observers\Impl;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\User\Observers\UserObserverInterface;

final class AuditLogObserver implements UserObserverInterface
{
    /**
     * {@inheritdoc}
     */
    public function update(User $user, string $event, array $metadata = []): void
    {
        if (! in_array($event, ['locked', 'unlocked'], true)) {
            return;
        }

        $actor = $metadata['actor'] ?? null;
        $oldStatus = $metadata['old_status'] ?? null;
        $newStatus = $metadata['new_status'] ?? null;

        AuditLog::create([
            'actor_id' => $actor?->id,
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'action' => $event === 'locked' ? 'admin.user.locked' : 'admin.user.unlocked',
            'changes' => [
                'status' => [
                    'old' => $oldStatus,
                    'new' => $newStatus,
                ],
            ],
            'metadata' => array_filter([
                'actor_role' => 'ADMIN',
                'reason_code' => $metadata['reason_code'] ?? null,
                'reason_label' => $metadata['reason_label'] ?? null,
                'reason_text' => $metadata['reason_text'] ?? null,
            ]),
        ]);
    }
}
