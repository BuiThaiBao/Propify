<?php

namespace App\Services\User\Observers;

interface UserSubjectInterface
{
    /**
     * Attach an observer to the subject.
     */
    public function attach(UserObserverInterface $observer): void;

    /**
     * Detach an observer from the subject.
     */
    public function detach(UserObserverInterface $observer): void;

    /**
     * Notify all attached observers of an event.
     */
    public function notify(string $event, array $metadata = []): void;
}
