<?php

namespace App\Listeners\Appointment;

use App\Enums\BookingStatus;
use App\Enums\NotificationChannelType;
use App\Enums\NotificationType;
use App\Events\Appointment\AppointmentBookingStatusUpdated;
use App\Models\AppointmentBooking;
use App\Services\Notification\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;

final class SendAppointmentBookingStatusNotification implements ShouldQueue
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    public function handle(AppointmentBookingStatusUpdated $event): void
    {
        $booking = AppointmentBooking::query()
            ->with(['slot.listing.property', 'slot.poster', 'viewer'])
            ->findOrFail($event->bookingId);

        $viewer = $booking->viewer;

        if (! $viewer) {
            return;
        }

        $status = BookingStatus::from($event->status);

        $this->notificationService->send(
            user: $viewer,
            type: NotificationType::APPOINTMENT_STATUS_UPDATED,
            data: [
                'booking_id' => $booking->id,
                'status' => $status->value,
                'listing_id' => $booking->slot->listing_id,
                'listing_title' => $booking->slot->listing->title
                    ?? $booking->slot->listing->property?->title
                    ?? 'Tin đăng',
                'meet_time' => $booking->meet_time?->format('H:i - d/m/Y'),
                'poster_name' => $booking->slot->poster?->full_name ?? 'Người đăng tin',
            ],
            channels: [NotificationChannelType::EMAIL, NotificationChannelType::DATABASE],
        );
    }
}
