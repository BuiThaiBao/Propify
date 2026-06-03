<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class AppointmentExpiredMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly array $data = [],
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[Propify] Lịch hẹn đã quá thời gian xác nhận và bị hủy'
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.appointment-expired');
    }
}
