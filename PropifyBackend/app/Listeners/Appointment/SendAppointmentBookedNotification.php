<?php

namespace App\Listeners\Appointment;

use App\Enums\NotificationChannelType;
use App\Enums\NotificationType;
use App\Events\Appointment\AppointmentBooked;
use App\Models\AppointmentBooking;
use App\Services\Notification\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;

final class SendAppointmentBookedNotification implements ShouldQueue
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    public function handle(AppointmentBooked $event): void
    {
        $booking = AppointmentBooking::query()
            ->with(['slot.listing.property', 'slot.poster', 'viewer'])
            ->findOrFail($event->bookingId);

        $poster = $booking->slot?->poster;

        if (! $poster) {
            return;
        }

        $this->notificationService->send(
            user: $poster,
            type: NotificationType::APPOINTMENT_BOOKED,
            data: [
                'booking_id' => $booking->id,
                'viewer_name' => $booking->full_name,
                'viewer_phone' => $booking->phone_number ?: $booking->viewer?->phone,
                'viewer_email' => $booking->email ?: $booking->viewer?->email,
                'meet_time' => $booking->meet_time?->format('H:i - d/m/Y'),
                'listing_id' => $booking->slot->listing_id,
                'listing_title' => $booking->slot->listing->title
                    ?? $booking->slot->listing->property?->title
                    ?? 'Tin đăng',
            ],
            channels: [NotificationChannelType::EMAIL, NotificationChannelType::DATABASE],
        );
    }
}
