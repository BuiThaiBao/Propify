<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Listing;
use App\Models\Package;
use App\Models\Property;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

final class AdminTransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_admin_transactions(): void
    {
        $response = $this->getJson('/api/v1/admin/transactions');

        $response->assertUnauthorized();
    }

    public function test_non_admin_cannot_access_admin_transactions(): void
    {
        $user = $this->createUser(role: UserRole::User);

        $response = $this->authorizedGet($user, '/api/v1/admin/transactions');

        $response->assertForbidden();
    }

    public function test_admin_can_list_transactions_with_summary_and_filters(): void
    {
        $admin = $this->createUser(role: UserRole::Admin, email: 'admin-transactions@example.com');
        $owner = $this->createUser(email: 'owner-transactions@example.com');
        $targetUser = $this->createUser(fullName: 'Nguyen Van A', email: 'buyer@example.com', phone: '0901234567');
        $otherUser = $this->createUser(fullName: 'Tran Thi B', email: 'other@example.com', phone: '0911111111');

        $basicPackage = $this->createPackage(name: 'Basic');
        $premiumPackage = $this->createPackage(name: 'Premium');

        $targetListing = $this->createListing($owner, $basicPackage, 'Can ho ban trung tam');
        $otherListing = $this->createListing($owner, $premiumPackage, 'Nha pho ngoai o');

        $successTransaction = $this->createTransaction(
            user: $targetUser,
            listing: $targetListing,
            package: $basicPackage,
            overrides: [
                'amount' => 350000,
                'status' => 'SUCCESS',
                'payment_method' => 'VNPAY',
                'transaction_date' => '2026-06-01 09:00:00',
                'vnp_txn_ref' => 'PFY-SEARCH-001',
                'vnp_transaction_no' => 'VN0001',
            ],
        );

        $pendingTransaction = $this->createTransaction(
            user: $targetUser,
            listing: $targetListing,
            package: $basicPackage,
            overrides: [
                'amount' => 300000,
                'status' => 'PENDING',
                'payment_method' => 'VNPAY',
                'transaction_date' => '2026-06-02 10:00:00',
                'vnp_txn_ref' => 'PFY-SEARCH-002',
                'vnp_transaction_no' => 'VN0002',
            ],
        );

        $this->createTransaction(
            user: $otherUser,
            listing: $otherListing,
            package: $premiumPackage,
            overrides: [
                'amount' => 700000,
                'status' => 'FAILED',
                'payment_method' => 'MOMO',
                'transaction_date' => '2026-05-29 11:00:00',
                'vnp_txn_ref' => 'PFY-OTHER-003',
                'vnp_transaction_no' => 'VN0003',
            ],
        );

        $response = $this->authorizedGet(
            $admin,
            '/api/v1/admin/transactions?search=0901234567&status=PENDING&package_id='.$basicPackage->id.'&min_amount=200000&max_amount=400000&from_date=2026-06-01&to_date=2026-06-03&per_page=10'
        );

        $response->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('meta.per_page', 10)
            ->assertJsonPath('data.0.id', $pendingTransaction->id)
            ->assertJsonPath('data.0.user.id', $targetUser->id)
            ->assertJsonPath('data.0.package.id', $basicPackage->id)
            ->assertJsonPath('summary.total_revenue', '350000.00')
            ->assertJsonPath('summary.counts.SUCCESS', 1)
            ->assertJsonPath('summary.counts.PENDING', 1)
            ->assertJsonPath('summary.counts.FAILED', 0);

        $this->assertNotSame($successTransaction->id, $pendingTransaction->id);
    }

    public function test_admin_transaction_validation_rejects_invalid_filters(): void
    {
        $admin = $this->createUser(role: UserRole::Admin, email: 'admin-validation@example.com');

        $response = $this->authorizedGet(
            $admin,
            '/api/v1/admin/transactions?per_page=1000&min_amount=-1&from_date=2026-06-03&to_date=2026-06-01'
        );

        $response->assertUnprocessable()
            ->assertJsonPath('status', false)
            ->assertJsonStructure(['errors' => ['per_page', 'min_amount', 'to_date']]);
    }

    public function test_admin_can_view_transaction_detail_with_notes(): void
    {
        $admin = $this->createUser(role: UserRole::Admin, email: 'admin-detail@example.com');
        $owner = $this->createUser(email: 'owner-detail@example.com');
        $buyer = $this->createUser(fullName: 'Le Thi C', email: 'buyer-detail@example.com');
        $package = $this->createPackage(name: 'Diamond');
        $listing = $this->createListing($owner, $package, 'Biet thu view song');
        $transaction = $this->createTransaction($buyer, $listing, $package, [
            'status' => 'SUCCESS',
            'amount' => 1200000,
            'vnp_txn_ref' => 'PFY-DETAIL-001',
            'vnp_transaction_no' => 'VN-DETAIL-001',
            'vnp_bank_code' => 'NCB',
            'vnp_response_code' => '00',
        ]);

        DB::table('transaction_notes')->insert([
            'transaction_id' => $transaction->id,
            'admin_id' => $admin->id,
            'note' => 'Da doi soat thanh cong',
            'created_at' => Carbon::parse('2026-06-03 09:30:00'),
        ]);

        $response = $this->authorizedGet($admin, "/api/v1/admin/transactions/{$transaction->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $transaction->id)
            ->assertJsonPath('data.user.id', $buyer->id)
            ->assertJsonPath('data.notes.0.note', 'Da doi soat thanh cong')
            ->assertJsonPath('data.notes.0.admin.id', $admin->id);
    }

    public function test_admin_can_add_transaction_note_without_overwriting_existing_note_history(): void
    {
        $admin = $this->createUser(role: UserRole::Admin, email: 'admin-note@example.com');
        $owner = $this->createUser(email: 'owner-note@example.com');
        $buyer = $this->createUser(email: 'buyer-note@example.com');
        $package = $this->createPackage(name: 'Gold');
        $listing = $this->createListing($owner, $package, 'Can ho cao cap');
        $transaction = $this->createTransaction($buyer, $listing, $package, ['status' => 'SUCCESS']);

        DB::table('transaction_notes')->insert([
            'transaction_id' => $transaction->id,
            'admin_id' => $admin->id,
            'note' => 'Ghi chu cu',
            'created_at' => now()->subDay(),
        ]);

        $response = $this->authorizedPost($admin, "/api/v1/admin/transactions/{$transaction->id}/notes", [
            'note' => 'Ghi chu moi',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.note', 'Ghi chu moi')
            ->assertJsonPath('data.admin.id', $admin->id);

        $this->assertDatabaseHas('transaction_notes', [
            'transaction_id' => $transaction->id,
            'admin_id' => $admin->id,
            'note' => 'Ghi chu cu',
        ]);
        $this->assertDatabaseHas('transaction_notes', [
            'transaction_id' => $transaction->id,
            'admin_id' => $admin->id,
            'note' => 'Ghi chu moi',
        ]);
    }

    public function test_admin_can_export_filtered_transactions_as_csv(): void
    {
        $admin = $this->createUser(role: UserRole::Admin, email: 'admin-export@example.com');
        $owner = $this->createUser(email: 'owner-export@example.com');
        $buyer = $this->createUser(fullName: 'Pham Van D', email: 'buyer-export@example.com');
        $package = $this->createPackage(name: 'Silver');
        $listing = $this->createListing($owner, $package, 'Nha pho mat tien');

        $matching = $this->createTransaction($buyer, $listing, $package, [
            'status' => 'SUCCESS',
            'amount' => 900000,
            'transaction_date' => '2026-06-03 08:00:00',
            'vnp_txn_ref' => 'PFY-EXPORT-001',
        ]);
        $this->createTransaction($buyer, $listing, $package, [
            'status' => 'FAILED',
            'amount' => 400000,
            'transaction_date' => '2026-05-01 08:00:00',
            'vnp_txn_ref' => 'PFY-EXPORT-002',
        ]);

        $response = $this->authorizedGet(
            $admin,
            '/api/v1/admin/transactions/export?status=SUCCESS&search=PFY-EXPORT-001'
        );

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString((string) $matching->id, $response->streamedContent());
        $this->assertStringContainsString('PFY-EXPORT-001', $response->streamedContent());
        $this->assertStringNotContainsString('PFY-EXPORT-002', $response->streamedContent());
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

    private function authorizedPost(User $user, string $uri, array $payload)
    {
        return $this->withHeaders([
            'Authorization' => 'Bearer '.JWTAuth::fromUser($user),
        ])->postJson($uri, $payload);
    }
}
