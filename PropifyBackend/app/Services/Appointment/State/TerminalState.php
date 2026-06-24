<?php

namespace App\Services\Appointment\State;

/**
 * Trạng thái kết thúc: CANCELLED_BY_VIEWER / CANCELLED_BY_POSTER / EXPIRED.
 * Mọi thao tác đều không hợp lệ (kế thừa hành vi ném BookingNotPending từ lớp cha).
 */
final class TerminalState extends AbstractBookingState {}
