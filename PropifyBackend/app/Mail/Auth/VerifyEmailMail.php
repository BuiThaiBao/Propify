<?php

namespace App\Mail\Auth;

use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class VerifyEmailMail extends Mailable
{
    use SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly array $data = []  // data['otp']
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Xác thực tài khoản RentHouse — Mã OTP của bạn'
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.auth.verify-email'
        );
    }
}
