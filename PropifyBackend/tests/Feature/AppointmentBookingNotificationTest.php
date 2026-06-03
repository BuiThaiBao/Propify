<?php

namespace Tests\Feature;

use App\Enums\NotificationType;
use App\Events\Appointment\AppointmentBooked;
use App\Listeners\Appointment\SendAppointmentBookedNotification;
use App\Mail\AppointmentBookedMail;
use App\Models\AppointmentBooking;
use App\Models\AppointmentSlot;
use App\Models\Listing;
use App\Models\Notification;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
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
}
