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

final class VnpayReturnTest extends TestCase
{
    use RefreshDatabase;

    public function test_vnpay_return_successful_payment_upgrades_package_and_logs(): void
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
            'payment_method' => 'VNPAY',
            'status' => 'PENDING',
            'transaction_date' => now(),
            'expires_at' => now()->addDay(),
            'vnp_txn_ref' => 'PFY'.'999', // Will mock this
        ]);

        // 3. Setup VNPAY config and build parameters
        config(['vnpay.hash_secret' => 'testsecret']);

        $params = [
            'vnp_TxnRef' => 'PFY'.$transaction->id,
            'vnp_TransactionNo' => '123456',
            'vnp_BankCode' => 'NCB',
            'vnp_ResponseCode' => '00',
            'vnp_TransactionStatus' => '00',
            'vnp_PayDate' => '20260604231828',
        ];

        // Sort parameters by key as VNPAY does
        ksort($params);

        // Build query string
        $query = http_build_query($params, '', '&', PHP_QUERY_RFC1738);

        // Calculate secure hash
        $secureHash = hash_hmac('sha512', $query, 'testsecret');

        // Add secure hash to parameters
        $params['vnp_SecureHash'] = $secureHash;

        // 4. Hit return route with simulated query parameters
        $response = $this->get('/api/v1/payments/vnpay/return?'.http_build_query($params));

        // 5. Assert redirection html response and database states
        $response->assertOk();
        $this->assertStringContainsString('Thanh toan VNPAY thanh cong', $response->content());

        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'status' => 'SUCCESS',
            'vnp_transaction_no' => '123456',
        ]);

        $this->assertDatabaseHas('listings', [
            'id' => $listing->id,
            'package_id' => $package->id,
        ]);
    }
}
