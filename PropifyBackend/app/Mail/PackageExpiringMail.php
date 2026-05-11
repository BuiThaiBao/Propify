<?php

namespace App\Mail;

use App\Models\Listing;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class PackageExpiringMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Listing $listing,
        public readonly string  $ownerName,
        public readonly string  $packageName,
        public readonly int     $daysLeft,
        public readonly Carbon  $expiresAt,
    ) {}

    public function envelope(): Envelope
    {
        $subject = $this->daysLeft === 0
            ? "[Propify] Gói {$this->packageName} sẽ hết hạn hôm nay!"
            : "[Propify] Gói {$this->packageName} sẽ hết hạn sau {$this->daysLeft} ngày";

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.package-expiring');
    }
}
