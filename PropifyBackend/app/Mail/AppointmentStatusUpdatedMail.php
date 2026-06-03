<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class AppointmentStatusUpdatedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly array $data = [],
    ) {}

    public function envelope(): Envelope
    {
        $subject = ($this->data['status'] ?? null) === 'APPROVED'
            ? '[Propify] Lịch hẹn của bạn đã được chấp nhận'
            : '[Propify] Lịch hẹn của bạn đã bị từ chối';

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.appointment-status-updated');
    }
}
