<?php

namespace App\Console\Commands;

use App\Enums\NotificationType;
use App\Events\Listing\ListingPackageExpiring;
use App\Models\Listing;
use App\Models\Notification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Phát event thông báo cho listing có gói sắp hết hạn trong 7 ngày.
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

            if (! $owner?->email) {
                continue;
            }

            $daysLeft = (int) now()->diffInDays($listing->package_expires_at, false);

            if ($daysLeft < 0) {
                continue; // Đã hết hạn, sẽ được xử lý bởi packages:expire
            }

            $alreadyNotified = Notification::query()
                ->where('user_id', $owner->id)
                ->where('type', NotificationType::PACKAGE_EXPIRING->value)
                ->where('data->listing_id', $listing->id)
                ->where('data->package_expires_at', $listing->package_expires_at->toDateTimeString())
                ->exists();

            if ($alreadyNotified) {
                continue;
            }

            ListingPackageExpiring::dispatch(
                listing: $listing,
                user: $owner,
                daysLeft: $daysLeft,
                expiresAt: $listing->package_expires_at,
            );

            $sentCount++;

            Log::debug('[NotifyExpiringPackages] Event dispatched', [
                'listing_id' => $listing->id,
                'owner_id' => $owner->id,
                'days_left' => $daysLeft,
            ]);
        }

        Log::info('[NotifyExpiringPackages] Completed', [
            'total_listings' => $listings->count(),
            'emails_sent' => $sentCount,
        ]);

        $this->info(sprintf('Dispatched %d expiration notifications.', $sentCount));

        return self::SUCCESS;
    }
}
