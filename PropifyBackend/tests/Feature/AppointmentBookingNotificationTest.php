<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\NotificationType;
use App\DTOs\Appointment\CreateBookingDto;
use App\Events\Appointment\AppointmentBooked;
use App\Events\Appointment\AppointmentBookingExpired;
use App\Jobs\AutoCancelExpiredBookingJob;
use App\Listeners\Appointment\SendAppointmentBookedNotification;
use App\Listeners\Appointment\SendAppointmentBookingExpiredNotification;
use App\Mail\AppointmentBookedMail;
use App\Mail\AppointmentExpiredMail;
use App\Mail\AppointmentStatusUpdatedMail;
use App\Models\AppointmentBooking;
use App\Models\AppointmentSlot;
use App\Models\Listing;
use App\Models\Notification;
use App\Models\Property;
use App\Models\User;
use App\Services\Appointment\AppointmentBookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

final class AppointmentBookingNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_listener_creates_notification_and_sends_mail_for_new_booking(): void
    {
        Mail::fake();

        $poster = User::query()->create([
            'full_name' => 'Chu nha',
            'phone' => '0901000001',
            'email' => 'poster@example.com',
        ]);

        $viewer = User::query()->create([
            'full_name' => 'Nguoi xem',
            'phone' => '0901000002',
            'email' => 'viewer@example.com',
        ]);

        $property = Property::query()->create([
            'type' => 'HOUSE',
            'province_code' => '01',
            'area' => 80,
            'price' => 2000000000,
            'poster_type' => 'OWNER',
            'contact_name' => 'Chu nha',
            'contact_phone' => '0901000001',
            'contact_email' => 'poster@example.com',
        ]);

        $listing = Listing::query()->create([
            'property_id' => $property->id,
            'owner_id' => $poster->id,
            'demand_type' => 'SALE',
            'title' => 'Can ho trung tam',
            'description' => 'Mo ta',
            'status' => 'ACTIVE',
            'score' => 10,
        ]);

        $slot = AppointmentSlot::query()->create([
            'listing_id' => $listing->id,
            'poster_id' => $poster->id,
            'day_of_week' => 2,
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'is_active' => true,
        ]);

        $booking = AppointmentBooking::query()->create([
            'slot_id' => $slot->id,
            'viewer_id' => $viewer->id,
            'meet_time' => now()->addDay(),
            'full_name' => 'Nguoi xem',
            'phone_number' => '0901000002',
            'email' => 'viewer@example.com',
            'note' => 'Hen xem nha',
            'status' => 'PENDING',
            'is_deleted' => false,
            'confirm_deadline' => now()->addHours(6),
            'is_urgent' => false,
        ]);

        $listener = $this->app->make(SendAppointmentBookedNotification::class);
        $listener->handle(new AppointmentBooked($booking->id));

        $notification = Notification::query()
            ->where('user_id', $poster->id)
            ->where('type', NotificationType::APPOINTMENT_BOOKED->value)
            ->first();

        $this->assertNotNull($notification);
        $this->assertSame($booking->id, $notification->data['booking_id']);
        $this->assertSame($listing->id, $notification->data['listing_id']);
        $this->assertSame('Nguoi xem', $notification->data['viewer_name']);

        Mail::assertQueued(AppointmentBookedMail::class, function (AppointmentBookedMail $mail) use ($poster, $booking) {
            return $mail->hasTo($poster->email)
                && ($mail->data['booking_id'] ?? null) === $booking->id;
        });
    }

    public function test_viewer_receives_notification_when_poster_approves_booking(): void
    {
        Mail::fake();

        [$poster, $viewer, $booking] = $this->createBookingFixture();

        app(AppointmentBookingService::class)->updateBookingStatus(
            bookingId: $booking->id,
            posterId: $poster->id,
            status: BookingStatus::APPROVED->value,
            note: null,
        );

        $notification = Notification::query()
            ->where('user_id', $viewer->id)
            ->where('type', NotificationType::APPOINTMENT_STATUS_UPDATED->value)
            ->first();

        $this->assertNotNull($notification);
        $this->assertSame(BookingStatus::APPROVED->value, $notification->data['status']);

        Mail::assertQueued(AppointmentStatusUpdatedMail::class, function (AppointmentStatusUpdatedMail $mail) use ($viewer, $booking) {
            return $mail->hasTo($viewer->email)
                && ($mail->data['booking_id'] ?? null) === $booking->id
                && ($mail->data['status'] ?? null) === BookingStatus::APPROVED->value;
        });
    }

    public function test_viewer_receives_notification_when_poster_rejects_booking(): void
    {
        Mail::fake();

        [$poster, $viewer, $booking] = $this->createBookingFixture();

        app(AppointmentBookingService::class)->updateBookingStatus(
            bookingId: $booking->id,
            posterId: $poster->id,
            status: BookingStatus::CANCELLED_BY_POSTER->value,
            note: 'Không phù hợp',
        );

        $notification = Notification::query()
            ->where('user_id', $viewer->id)
            ->where('type', NotificationType::APPOINTMENT_STATUS_UPDATED->value)
            ->first();

        $this->assertNotNull($notification);
        $this->assertSame(BookingStatus::CANCELLED_BY_POSTER->value, $notification->data['status']);

        Mail::assertQueued(AppointmentStatusUpdatedMail::class, function (AppointmentStatusUpdatedMail $mail) use ($viewer, $booking) {
            return $mail->hasTo($viewer->email)
                && ($mail->data['booking_id'] ?? null) === $booking->id
                && ($mail->data['status'] ?? null) === BookingStatus::CANCELLED_BY_POSTER->value;
        });
    }

    public function test_viewer_can_book_same_slot_again_after_cancelled_or_expired_booking(): void
    {
        Queue::fake();
        Event::fake();

        foreach ([
            BookingStatus::CANCELLED_BY_VIEWER,
            BookingStatus::CANCELLED_BY_POSTER,
            BookingStatus::EXPIRED,
        ] as $status) {
            [$poster, $viewer, $booking] = $this->createBookingFixture($status);

            $created = app(AppointmentBookingService::class)->createBooking(new CreateBookingDto(
                slotId: (int) $booking->slot_id,
                viewerId: (int) $viewer->id,
                date: $booking->meet_time->toDateString(),
                fullName: 'Viewer',
                phone: '0901000012',
                email: 'viewer-fixture@example.com',
                note: 'Dat lai lich',
            ));

            $this->assertSame(BookingStatus::PENDING->value, $created->status);
            $this->assertSame((int) $booking->slot_id, (int) $created->slot_id);
            $this->assertSame($booking->meet_time->format('Y-m-d H:i:s'), $created->meet_time->format('Y-m-d H:i:s'));
            $this->assertNotSame((int) $booking->id, (int) $created->id);
        }
    }

    public function test_auto_cancel_expired_booking_dispatches_expired_event(): void
    {
        Event::fake([AppointmentBookingExpired::class]);

        [, , $booking] = $this->createBookingFixture();

        (new AutoCancelExpiredBookingJob($booking->id))->handle();

        $booking->refresh();

        $this->assertSame(BookingStatus::EXPIRED->value, $booking->status);

        Event::assertDispatched(AppointmentBookingExpired::class, function (AppointmentBookingExpired $event) use ($booking) {
            return $event->bookingId === $booking->id;
        });
    }

    public function test_expired_booking_notification_is_sent_to_viewer_and_poster(): void
    {
        Mail::fake();

        [$poster, $viewer, $booking] = $this->createBookingFixture(BookingStatus::EXPIRED);

        $listener = $this->app->make(SendAppointmentBookingExpiredNotification::class);
        $listener->handle(new AppointmentBookingExpired($booking->id));

        $this->assertSame(2, Notification::query()
            ->where('type', NotificationType::APPOINTMENT_EXPIRED->value)
            ->whereIn('user_id', [$poster->id, $viewer->id])
            ->count());

        Mail::assertQueued(AppointmentExpiredMail::class, function (AppointmentExpiredMail $mail) use ($poster) {
            return $mail->hasTo($poster->email);
        });

        Mail::assertQueued(AppointmentExpiredMail::class, function (AppointmentExpiredMail $mail) use ($viewer) {
            return $mail->hasTo($viewer->email);
        });
    }

    private function createBookingFixture(BookingStatus $status = BookingStatus::PENDING): array
    {
        static $sequence = 0;

        $sequence++;
        $posterEmail = "poster-fixture-{$sequence}@example.com";
        $viewerEmail = "viewer-fixture-{$sequence}@example.com";
        $posterPhone = '090100'.str_pad((string) ($sequence * 2 + 10), 4, '0', STR_PAD_LEFT);
        $viewerPhone = '090100'.str_pad((string) ($sequence * 2 + 11), 4, '0', STR_PAD_LEFT);

        $poster = User::query()->create([
            'full_name' => 'Poster',
            'phone' => $posterPhone,
            'email' => $posterEmail,
        ]);

        $viewer = User::query()->create([
            'full_name' => 'Viewer',
            'phone' => $viewerPhone,
            'email' => $viewerEmail,
        ]);

        $property = Property::query()->create([
            'type' => 'HOUSE',
            'province_code' => '01',
            'area' => 90,
            'price' => 2500000000,
            'poster_type' => 'OWNER',
            'contact_name' => 'Poster',
            'contact_phone' => $posterPhone,
            'contact_email' => $posterEmail,
        ]);

        $listing = Listing::query()->create([
            'property_id' => $property->id,
            'owner_id' => $poster->id,
            'demand_type' => 'SALE',
            'title' => 'Listing fixture',
            'description' => 'Fixture',
            'status' => 'ACTIVE',
            'score' => 10,
        ]);

        $slot = AppointmentSlot::query()->create([
            'listing_id' => $listing->id,
            'poster_id' => $poster->id,
            'day_of_week' => 2,
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'is_active' => true,
        ]);

        $meetTime = Carbon::now()->next(Carbon::TUESDAY)->setTime(9, 0);

        $booking = AppointmentBooking::query()->create([
            'slot_id' => $slot->id,
            'viewer_id' => $viewer->id,
            'meet_time' => $meetTime,
            'full_name' => 'Viewer',
            'phone_number' => $viewerPhone,
            'email' => $viewerEmail,
            'note' => 'Hen xem nha',
            'status' => $status->value,
            'is_deleted' => false,
            'confirm_deadline' => now()->addHours(6),
            'is_urgent' => false,
        ]);

        return [$poster, $viewer, $booking];
    }
}
