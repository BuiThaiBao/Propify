<?php

namespace App\Console\Commands;

use App\Mail\PackageExpiringMail;
use App\Models\Listing;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Gửi email thông báo gói tin sắp hết hạn.
 *
 * Logic:
 *   - Tìm listings có package_expires_at trong vòng 7 ngày tới
 *   - Gửi 1 email/ngày cho owner
 *   - Email chứa nút gia hạn
 *
 * Chạy: Mỗi ngày 1 lần (9h sáng)
 */
final class NotifyExpiringPackages extends Command
{
    protected $signature = 'packages:notify-expiring';
    protected $description = 'Send daily email to owners of listings with packages expiring within 7 days';

    public function handle(): int
    {
        // Tìm listings có gói sắp hết hạn (trong 7 ngày tới)
        $listings = Listing::query()
            ->whereNotNull('package_id')
            ->whereNotNull('package_expires_at')
            ->where('package_expires_at', '>', now())
            ->where('package_expires_at', '<=', now()->addDays(7))
            ->with(['owner:id,full_name,email', 'package:id,name,slug,badge,color'])
            ->get();

        if ($listings->isEmpty()) {
            $this->line('No expiring packages to notify.');
            return self::SUCCESS;
        }

        $sentCount = 0;

        foreach ($listings as $listing) {
            $owner = $listing->owner;

            if (!$owner?->email) {
                continue;
            }

            $daysLeft = (int) now()->diffInDays($listing->package_expires_at, false);

            if ($daysLeft < 0) {
                continue; // Đã hết hạn, sẽ được xử lý bởi packages:expire
            }

            try {
                Mail::to($owner->email)->queue(
                    new PackageExpiringMail(
                        listing: $listing,
                        ownerName: $owner->full_name ?? 'Quý khách',
                        packageName: $listing->package?->name ?? 'Gói tin',
                        daysLeft: $daysLeft,
                        expiresAt: $listing->package_expires_at,
                    )
                );

                $sentCount++;

                Log::debug('[NotifyExpiringPackages] Email queued', [
                    'listing_id' => $listing->id,
                    'owner_id'   => $owner->id,
                    'days_left'  => $daysLeft,
                ]);
            } catch (\Throwable $e) {
                Log::error('[NotifyExpiringPackages] Failed to send email', [
                    'listing_id' => $listing->id,
                    'error'      => $e->getMessage(),
                ]);
            }
        }

        Log::info('[NotifyExpiringPackages] Completed', [
            'total_listings' => $listings->count(),
            'emails_sent'    => $sentCount,
        ]);

        $this->info(sprintf('Queued %d expiration notification emails.', $sentCount));

        return self::SUCCESS;
    }
}
