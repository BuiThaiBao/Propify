<?php

namespace App\Listeners\Appointment;

use App\Enums\NotificationChannelType;
use App\Enums\NotificationType;
use App\Events\Appointment\AppointmentBookingExpired;
use App\Models\AppointmentBooking;
use App\Services\Notification\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;

final class SendAppointmentBookingExpiredNotification implements ShouldQueue
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    public function handle(AppointmentBookingExpired $event): void
    {
        $booking = AppointmentBooking::query()
            ->with(['slot.listing.property', 'slot.poster', 'viewer'])
            ->findOrFail($event->bookingId);

        $data = [
            'booking_id' => $booking->id,
            'status' => $booking->status,
            'listing_id' => $booking->slot->listing_id,
            'listing_title' => $booking->slot->listing->title
                ?? $booking->slot->listing->property?->title
                ?? 'Tin dang',
            'meet_time' => $booking->meet_time?->format('H:i - d/m/Y'),
            'confirm_deadline' => $booking->confirm_deadline?->format('H:i - d/m/Y'),
            'viewer_name' => $booking->full_name,
            'poster_name' => $booking->slot->poster?->full_name ?? 'Nguoi dang tin',
        ];

        foreach ([$booking->viewer, $booking->slot?->poster] as $user) {
            if (! $user) {
                continue;
            }

            $this->notificationService->send(
                user: $user,
                type: NotificationType::APPOINTMENT_EXPIRED,
                data: $data + ['recipient_role' => (int) $user->id === (int) $booking->viewer_id ? 'viewer' : 'poster'],
                channels: [NotificationChannelType::EMAIL, NotificationChannelType::DATABASE],
            );
        }
    }
}
