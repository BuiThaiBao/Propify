<?php

namespace App\Services\Listing\Upgrade;

use App\Jobs\ExpirePendingTransactionJob;
use App\Models\Listing;
use App\Models\Package;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Payment\Gateway\PaymentProviderFactory;

final class CreateUpgradePaymentCommand
{
    public function __construct(
        private readonly PaymentProviderFactory $paymentProviderFactory,
    ) {}

    public function execute(
        User $user,
        Listing $listing,
        Package $newPackage,
        int $durationDays,
        float $amount,
        string $clientIp
    ): string {
        $paymentExpiresAt = now()->addMinutes(15);

        $transaction = Transaction::create([
            'user_id' => $user->id,
            'listing_id' => $listing->id,
            'package_id' => $newPackage->id,
            'amount' => $amount,
            'duration_days' => $durationDays,
            'payment_method' => 'VNPAY',
            'status' => 'PENDING',
            'transaction_date' => now(),
            'expires_at' => $paymentExpiresAt,
        ]);

        $transaction->update(['vnp_txn_ref' => 'PFY'.$transaction->id]);
        ExpirePendingTransactionJob::dispatch($transaction->id)->delay($paymentExpiresAt);

        return $this->paymentProviderFactory->for('VNPAY')->createPaymentUrl($transaction->fresh(), $clientIp);
    }
}
