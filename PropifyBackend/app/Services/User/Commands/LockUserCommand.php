<?php

namespace App\Services\User\Commands;

use App\Models\User;
use App\Services\Notification\InAppNotificationService;
use App\Services\User\Observers\Impl\AuditLogObserver;
use App\Services\User\Observers\Impl\NotificationObserver;
use App\Services\User\Observers\UserSubject;

final class LockUserCommand implements UserCommandInterface
{
    /**
     * LockUserCommand constructor.
     */
    public function __construct(
        private readonly User $user,
        private readonly User $actor,
        private readonly array $data,
        private readonly InAppNotificationService $notificationService
    ) {}

    /**
     * {@inheritdoc}
     */
    public function execute(): User
    {
        $oldStatus = $this->user->status;

        // Transition status via State Pattern
        $state = $this->user->getState();
        $state->lock();

        $newStatus = $this->user->status;

        // If status changed, notify observers
        if ($oldStatus !== $newStatus) {
            $subject = new UserSubject($this->user);
            $subject->attach(new AuditLogObserver);
            $subject->attach(new NotificationObserver($this->notificationService));

            $subject->notify('locked', [
                'actor' => $this->actor,
                'old_status' => $oldStatus?->value,
                'new_status' => $newStatus?->value,
                'reason_code' => $this->data['reason_code'] ?? null,
                'reason_label' => $this->data['reason_label'] ?? null,
                'reason_text' => $this->data['reason_text'] ?? null,
            ]);
        }

        return $this->user;
    }
}
