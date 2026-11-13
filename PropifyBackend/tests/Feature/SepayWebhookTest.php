<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Listing;
use App\Models\Package;
use App\Models\PackagePricing;
use App\Models\Property;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SepayWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_a_matching_signed_incoming_event_completes_an_upgrade_once(): void
    {
        // 1. Setup User, Package, Listing
        $user = User::create([
            'full_name' => 'Nguyen Van A',
            'email' => 'buyer@example.com',
            'phone' => '0901234567',
            'password' => 'Password123',
            'role' => UserRole::User->value,
            'status' => UserStatus::Active->value,
        ]);

        $package = Package::create([
            'name' => 'Diamond',
            'slug' => 'diamond',
            'price' => 100000,
            'priority' => 1,
            'multiplier' => 1,
            'daily_quota' => 10,
            'decay_rate' => 0.1,
            'is_active' => true,
        ]);

        PackagePricing::create([
            'package_id' => $package->id,
            'duration_days' => 30,
            'price' => 250000,
            'label' => '30 Days Diamond Upgrade',
            'is_active' => true,
        ]);

        $property = Property::create([
            'owner_id' => $user->id,
            'type' => 'APARTMENT',
            'province_code' => '79',
            'ward_code' => '001',
            'street_code' => 'street-1',
            'address_detail' => '123 Test Street',
            'area' => 75,
            'price' => 1500000000,
            'bedrooms' => 2,
            'bathrooms' => 2,
        ]);

        $listing = Listing::create([
            'property_id' => $property->id,
            'owner_id' => $user->id,
            'demand_type' => 'SALE',
            'title' => 'Listing Test',
            'description' => 'Description test',
            'status' => 'ACTIVE',
            'package_id' => $package->id,
        ]);

        // 2. Create PENDING upgrade transaction
        $transaction = Transaction::create([
            'user_id' => $user->id,
            'listing_id' => $listing->id,
            'package_id' => $package->id,
            'amount' => 250000,
            'duration_days' => 30,
            'payment_method' => 'SEPAY',
            'status' => 'PENDING',
            'transaction_date' => now(),
            'expires_at' => now()->addDay(),
        ]);

        config([
            'sepay.webhook_secret' => 'sepay-test-secret',
            'sepay.test_account' => '0000000001',
            'sepay.test_va' => 'SBSEPAYEXAMPLE12345',
        ]);

        $event = [
            'id' => 12345,
            'accountNumber' => '0000000001',
            'subAccount' => 'SBSEPAYEXAMPLE12345',
            'transferType' => 'in',
            'transferAmount' => 250000,
            'content' => 'SPY'.$transaction->id,
        ];

        $this->postJson('/api/v1/payments/sepay/webhook', $event)->assertUnauthorized();
        $this->deliver(array_replace($event, ['transferAmount' => 249999]));
        $this->deliver(array_replace($event, ['subAccount' => 'SBSEPAYOTHER']));
        $this->assertSame('PENDING', $transaction->fresh()->status);

        $this->deliver($event);
        $transaction->refresh();
        $this->assertSame('SUCCESS', $transaction->status);
        $this->assertSame(12345, (int) $transaction->sepay_event_id);
        $expiresAt = $listing->fresh()->package_expires_at;
        $this->assertNotNull($expiresAt);

        $this->deliver($event);
        $this->assertEquals($expiresAt, $listing->fresh()->package_expires_at);
    }

    private function deliver(array $event): void
    {
        $timestamp = (string) now()->timestamp;
        $body = json_encode($event, JSON_THROW_ON_ERROR);
        $signature = hash_hmac('sha256', $timestamp.'.'.$body, 'sepay-test-secret');

        $this->call('POST', '/api/v1/payments/sepay/webhook', server: [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_SEPAY_TIMESTAMP' => $timestamp,
            'HTTP_X_SEPAY_SIGNATURE' => 'sha256='.$signature,
        ], content: $body)->assertOk()->assertExactJson(['success' => true]);
    }
}
