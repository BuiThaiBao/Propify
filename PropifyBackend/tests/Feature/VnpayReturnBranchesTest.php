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
use Illuminate\Support\Carbon;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Khoá các nhánh xử lý callback VNPay (ngoài happy-path đã có ở VnpayReturnTest):
 * thanh toán thất bại, chữ ký sai, idempotent khi đã SUCCESS, và paid-nhưng-hết-hạn.
 */
final class VnpayReturnBranchesTest extends TestCase
{
    use RefreshDatabase;

    private function makeTransaction(string $status, ?Carbon $expiresAt): Transaction
    {
        $user = User::create([
            'full_name' => 'Buyer',
            'email' => 'buyer'.uniqid().'@example.com',
            'phone' => '09'.substr((string) microtime(true), -9),
            'password' => 'Password123',
            'role' => UserRole::User->value,
            'status' => UserStatus::Active->value,
        ]);

        $package = Package::create([
            'name' => 'Diamond',
            'slug' => 'diamond-'.uniqid(),
            'price' => 100000,
            'priority' => 1,
            'is_active' => true,
        ]);

        $property = Property::create([
            'owner_id' => $user->id,
            'type' => 'APARTMENT',
            'province_code' => '79',
            'area' => 75,
            'price' => 1500000000,
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

        return Transaction::create([
            'user_id' => $user->id,
            'listing_id' => $listing->id,
            'package_id' => $package->id,
            'amount' => 250000,
            'duration_days' => 30,
            'payment_method' => 'VNPAY',
            'status' => $status,
            'transaction_date' => now(),
            'expires_at' => $expiresAt,
        ]);
    }

    /**
     * @param  array<string, string>  $overrides
     */
    private function callReturn(Transaction $transaction, array $overrides = [], bool $validSignature = true): TestResponse
    {
        config(['vnpay.hash_secret' => 'testsecret']);

        $params = array_merge([
            'vnp_TxnRef' => 'PFY'.$transaction->id,
            'vnp_TransactionNo' => '123456',
            'vnp_BankCode' => 'NCB',
            'vnp_ResponseCode' => '00',
            'vnp_TransactionStatus' => '00',
            'vnp_PayDate' => '20260604231828',
        ], $overrides);

        ksort($params);
        $query = http_build_query($params, '', '&', PHP_QUERY_RFC1738);
        $params['vnp_SecureHash'] = $validSignature
            ? hash_hmac('sha512', $query, 'testsecret')
            : 'deadbeef';

        return $this->get('/api/v1/payments/vnpay/return?'.http_build_query($params));
    }

    public function test_failed_response_code_marks_pending_transaction_failed(): void
    {
        $transaction = $this->makeTransaction('PENDING', now()->addDay());

        $response = $this->callReturn($transaction, ['vnp_ResponseCode' => '07', 'vnp_TransactionStatus' => '02']);

        $response->assertOk();
        $this->assertStringContainsString('Thanh toan VNPAY that bai', $response->content());
        $this->assertDatabaseHas('transactions', ['id' => $transaction->id, 'status' => 'FAILED']);
    }

    public function test_invalid_signature_marks_pending_transaction_failed(): void
    {
        $transaction = $this->makeTransaction('PENDING', now()->addDay());

        $response = $this->callReturn($transaction, [], validSignature: false);

        $response->assertOk();
        $this->assertStringContainsString('Thanh toan VNPAY that bai', $response->content());
        $this->assertDatabaseHas('transactions', ['id' => $transaction->id, 'status' => 'FAILED']);
    }

    public function test_already_success_transaction_is_idempotent(): void
    {
        $transaction = $this->makeTransaction('SUCCESS', now()->addDay());

        $response = $this->callReturn($transaction);

        $response->assertOk();
        $this->assertStringContainsString('Thanh toan VNPAY thanh cong', $response->content());
        $this->assertDatabaseHas('transactions', ['id' => $transaction->id, 'status' => 'SUCCESS']);
    }

    public function test_paid_but_expired_pending_transaction_marked_expired(): void
    {
        $transaction = $this->makeTransaction('PENDING', now()->subMinute());

        $response = $this->callReturn($transaction);

        $response->assertOk();
        $this->assertStringContainsString('Thanh toan VNPAY that bai', $response->content());
        $this->assertDatabaseHas('transactions', ['id' => $transaction->id, 'status' => 'EXPIRED']);
    }
}
