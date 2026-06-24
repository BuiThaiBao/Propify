<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\AppointmentBooking;
use App\Models\AppointmentSlot;
use App\Models\Listing;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Kiểm tra rule: lịch ĐÃ XÁC NHẬN (APPROVED) khi qua giờ kết thúc hẹn (end_time của slot)
 * sẽ tự động chuyển sang HOÀN THÀNH (COMPLETED) qua lệnh appointments:complete-past.
 */
final class AppointmentAutoCompleteTest extends TestCase
{
    use RefreshDatabase;

    private int $seq = 0;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function makeBooking(string $status, string $meetTime, string $slotStart, string $slotEnd): AppointmentBooking
    {
        $this->seq++;
        $phone = '09010'.str_pad((string) $this->seq, 5, '0', STR_PAD_LEFT);

        $poster = User::query()->create([
            'full_name' => 'Poster',
            'phone' => $phone,
            'email' => "poster{$this->seq}@example.com",
        ]);
        $viewer = User::query()->create([
            'full_name' => 'Viewer',
            'phone' => '08010'.str_pad((string) $this->seq, 5, '0', STR_PAD_LEFT),
            'email' => "viewer{$this->seq}@example.com",
        ]);
        $property = Property::query()->create([
            'owner_id' => $poster->id,
            'type' => 'HOUSE',
            'province_code' => '01',
            'area' => 80,
            'price' => 2000000000,
            'poster_type' => 'OWNER',
        ]);
        $listing = Listing::query()->create([
            'property_id' => $property->id,
            'owner_id' => $poster->id,
            'demand_type' => 'SALE',
            'title' => 'Listing '.$this->seq,
            'description' => 'desc',
            'status' => 'ACTIVE',
            'score' => 10,
        ]);
        $slot = AppointmentSlot::query()->create([
            'listing_id' => $listing->id,
            'poster_id' => $poster->id,
            'day_of_week' => 2,
            'start_time' => $slotStart,
            'end_time' => $slotEnd,
            'is_active' => true,
        ]);

        return AppointmentBooking::query()->create([
            'slot_id' => $slot->id,
            'viewer_id' => $viewer->id,
            'meet_time' => $meetTime,
            'full_name' => 'Viewer',
            'phone_number' => $viewer->phone,
            'email' => $viewer->email,
            'note' => null,
            'status' => $status,
            'is_deleted' => false,
            'confirm_deadline' => now(),
            'is_urgent' => false,
        ]);
    }

    public function test_approved_booking_past_end_time_becomes_completed(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-21 10:00:00'));

        // Đã xác nhận, slot 08:00–09:00 cùng ngày → đã qua 09:00 → HOÀN THÀNH.
        $booking = $this->makeBooking(BookingStatus::APPROVED->value, '2026-06-21 08:00:00', '08:00:00', '09:00:00');

        $this->artisan('appointments:complete-past')->assertSuccessful();

        $this->assertSame(BookingStatus::COMPLETED->value, $booking->refresh()->status);
    }

    public function test_approved_booking_still_within_time_stays_approved(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-21 10:00:00'));

        // Đã bắt đầu (09:00) nhưng chưa kết thúc (11:00 > 10:00) → vẫn ĐÃ XÁC NHẬN.
        $booking = $this->makeBooking(BookingStatus::APPROVED->value, '2026-06-21 09:00:00', '09:00:00', '11:00:00');

        $this->artisan('appointments:complete-past')->assertSuccessful();

        $this->assertSame(BookingStatus::APPROVED->value, $booking->refresh()->status);
    }

    public function test_pending_booking_past_end_time_is_not_completed(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-21 10:00:00'));

        // Chờ xử lý dù đã qua giờ → KHÔNG đụng tới (chỉ hoàn thành lịch đã xác nhận).
        $booking = $this->makeBooking(BookingStatus::PENDING->value, '2026-06-21 08:00:00', '08:00:00', '09:00:00');

        $this->artisan('appointments:complete-past')->assertSuccessful();

        $this->assertSame(BookingStatus::PENDING->value, $booking->refresh()->status);
    }
}
