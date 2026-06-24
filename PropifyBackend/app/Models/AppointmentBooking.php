<?php

namespace App\Models;

use App\Enums\BookingStatus;
use App\Services\Appointment\State\ApprovedState;
use App\Services\Appointment\State\BookingState;
use App\Services\Appointment\State\PendingState;
use App\Services\Appointment\State\TerminalState;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class AppointmentBooking extends Model
{
    protected $table = 'appointment_bookings';

    protected $fillable = [
        'slot_id',
        'viewer_id',
        'meet_time',
        'full_name',
        'phone_number',
        'email',
        'note',
        'is_deleted',
        'status',
        'confirm_deadline',
        'is_urgent',
    ];

    protected $casts = [
        'meet_time' => 'datetime',
        'confirm_deadline' => 'datetime',
        'is_deleted' => 'boolean',
        'is_urgent' => 'boolean',
        'status' => 'string',
    ];

    // ==================== Relationships ====================

    /** Slot lịch hẹn mà booking này thuộc về */
    public function slot(): BelongsTo
    {
        return $this->belongsTo(AppointmentSlot::class, 'slot_id');
    }

    /** Người đặt lịch (khách xem nhà) */
    public function viewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'viewer_id');
    }

    // ==================== State ====================

    /**
     * Trả về đối tượng State tương ứng trạng thái hiện tại (State pattern).
     * Luật chuyển trạng thái được đóng gói trong các lớp State, không rải if ở service.
     */
    public function state(): BookingState
    {
        return match (BookingStatus::from($this->status)) {
            BookingStatus::PENDING => new PendingState,
            BookingStatus::APPROVED => new ApprovedState,
            default => new TerminalState,
        };
    }
}
