<?php

namespace App\Services\User\Observers;

use App\Models\User;

final class UserSubject implements UserSubjectInterface
{
    /**
     * @var UserObserverInterface[]
     */
    private array $observers = [];

    /**
     * UserSubject constructor.
     */
    public function __construct(private readonly User $user) {}

    /**
     * {@inheritdoc}
     */
    public function attach(UserObserverInterface $observer): void
    {
        $this->observers[] = $observer;
    }

    /**
     * {@inheritdoc}
     */
    public function detach(UserObserverInterface $observer): void
    {
        $this->observers = array_filter(
            $this->observers,
            static fn (UserObserverInterface $obs) => $obs !== $observer
        );
    }

    /**
     * {@inheritdoc}
     */
    public function notify(string $event, array $metadata = []): void
    {
        foreach ($this->observers as $observer) {
            $observer->update($this->user, $event, $metadata);
        }
    }
}
