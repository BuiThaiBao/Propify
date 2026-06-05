<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Listing;
use App\Models\Package;
use App\Models\Property;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

final class UserTransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_user_transactions(): void
    {
        $response = $this->getJson('/api/v1/user/transactions');

        $response->assertUnauthorized();
    }

    public function test_user_can_list_own_transactions_with_filters(): void
    {
        $user = $this->createUser(email: 'user-transactions@example.com');
        $otherUser = $this->createUser(email: 'other-transactions@example.com');

        $package = $this->createPackage(name: 'Basic');
        $listing = $this->createListing($user, $package, 'Can ho ban trung tam');

        $mySuccessTransaction = $this->createTransaction(
            user: $user,
            listing: $listing,
            package: $package,
            overrides: [
                'amount' => 350000,
                'status' => 'SUCCESS',
                'transaction_date' => '2026-06-01 09:00:00',
            ],
        );

        $myPendingTransaction = $this->createTransaction(
            user: $user,
            listing: $listing,
            package: $package,
            overrides: [
                'amount' => 300000,
                'status' => 'PENDING',
                'transaction_date' => '2026-06-02 10:00:00',
            ],
        );

        $otherTransaction = $this->createTransaction(
            user: $otherUser,
            listing: $listing,
            package: $package,
            overrides: [
                'amount' => 700000,
                'status' => 'SUCCESS',
                'transaction_date' => '2026-05-29 11:00:00',
            ],
        );

        // Test list all my transactions (should only return my 2 transactions)
        $response = $this->authorizedGet($user, '/api/v1/user/transactions');
        $response->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 2);

        // Test status filtering (should only return my SUCCESS transaction)
        $response = $this->authorizedGet($user, '/api/v1/user/transactions?status=SUCCESS');
        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $mySuccessTransaction->id);
    }

    private function createUser(
        UserRole $role = UserRole::User,
        ?string $email = null,
        ?string $phone = null,
        ?string $fullName = null,
    ): User {
        return User::create([
            'full_name' => $fullName ?? fake()->name(),
            'email' => $email ?? fake()->unique()->safeEmail(),
            'phone' => $phone ?? fake()->unique()->numerify('09########'),
            'password' => 'Password123',
            'role' => $role->value,
            'status' => UserStatus::Active->value,
        ]);
    }

    private function createPackage(string $name): Package
    {
        return Package::create([
            'name' => $name,
            'slug' => str($name)->slug()->toString().'-'.fake()->unique()->numberBetween(100, 999),
            'price' => 100000,
            'priority' => 1,
            'multiplier' => 1,
            'daily_quota' => 10,
            'decay_rate' => 0.1,
            'badge' => null,
            'color' => null,
            'is_active' => true,
        ]);
    }

    private function createListing(User $owner, Package $package, string $title): Listing
    {
        $property = Property::create([
            'owner_id' => $owner->id,
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

        return Listing::create([
            'property_id' => $property->id,
            'owner_id' => $owner->id,
            'demand_type' => 'SALE',
            'title' => $title,
            'description' => 'Mo ta test',
            'status' => 'ACTIVE',
            'package_id' => $package->id,
        ]);
    }

    private function createTransaction(User $user, Listing $listing, Package $package, array $overrides = []): Transaction
    {
        return Transaction::create(array_merge([
            'user_id' => $user->id,
            'listing_id' => $listing->id,
            'package_id' => $package->id,
            'amount' => 250000,
            'duration_days' => 30,
            'payment_method' => 'VNPAY',
            'status' => 'PENDING',
            'transaction_date' => now(),
            'expires_at' => now()->addDay(),
            'vnp_txn_ref' => 'PFY-'.fake()->unique()->numberBetween(1000, 9999),
            'vnp_transaction_no' => (string) fake()->unique()->numberBetween(100000, 999999),
            'vnp_bank_code' => 'NCB',
            'vnp_response_code' => null,
            'vnp_pay_date' => null,
        ], $overrides));
    }

    private function authorizedGet(User $user, string $uri)
    {
        return $this->withHeaders([
            'Authorization' => 'Bearer '.JWTAuth::fromUser($user),
        ])->getJson($uri);
    }
}
