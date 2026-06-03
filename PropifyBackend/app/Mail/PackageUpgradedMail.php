<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class PackageUpgradedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly array $data = [],
    ) {}

    public function envelope(): Envelope
    {
        $packageName = $this->data['package_name'] ?? 'gói tin';

        return new Envelope(subject: "[Propify] Nâng cấp {$packageName} thành công");
    }

    public function content(): Content
    {
        return new Content(view: 'emails.package-upgraded');
    }
}
