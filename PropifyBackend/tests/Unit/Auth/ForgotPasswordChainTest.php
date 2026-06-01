<?php

namespace Tests\Unit\Auth;

use App\Enums\OtpContext;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Services\Auth\ForgotPassword\ForgotPasswordChain;
use App\Services\Auth\ForgotPassword\Handlers\FindResetUserHandler;
use App\Services\Auth\ForgotPassword\Handlers\LogResetAttemptHandler;
use App\Services\Auth\ForgotPassword\Handlers\SendResetOtpHandler;
use App\Services\Otp\OtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

final class ForgotPasswordChainTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_sends_reset_otp_and_logs_for_existing_user(): void
    {
        Log::spy();

        $user = User::create([
            'full_name' => 'User',
            'email' => 'user@example.com',
            'password' => 'Password123',
            'role' => UserRole::User->value,
            'status' => UserStatus::Active->value,
        ]);

        $repository = Mockery::mock(UserRepository::class);
        $repository->shouldReceive('findByEmail')
            ->once()
            ->with('user@example.com')
            ->andReturn($user);

        $otpService = Mockery::mock(OtpService::class);
        $otpService->shouldReceive('generate')
            ->once()
            ->with($user, OtpContext::RESET_PASSWORD)
            ->andReturn('123456');

        $chain = $this->makeChain($repository, $otpService);
        $chain->execute('user@example.com');

        $this->assertDatabaseHas('users', ['email' => 'user@example.com']);

        Log::shouldHaveReceived('info')
            ->once()
            ->with('Password reset OTP sent', ['user_id' => $user->id]);
    }

    public function test_it_stops_chain_for_unknown_email(): void
    {
        Log::spy();

        $repository = Mockery::mock(UserRepository::class);
        $repository->shouldReceive('findByEmail')
            ->once()
            ->with('missing@example.com')
            ->andReturnNull();

        $otpService = Mockery::mock(OtpService::class);
        $otpService->shouldNotReceive('generate');

        $chain = $this->makeChain($repository, $otpService);
        $chain->execute('missing@example.com');

        $this->assertDatabaseMissing('users', ['email' => 'missing@example.com']);

        Log::shouldHaveReceived('warning')
            ->once()
            ->with('Forgot password for unknown email', ['email' => 'missing@example.com']);
    }

    private function makeChain(UserRepository $repository, OtpService $otpService): ForgotPasswordChain
    {
        $findUser = new FindResetUserHandler($repository);
        $sendOtp = new SendResetOtpHandler($otpService);
        $logAttempt = new LogResetAttemptHandler;

        $findUser->setNext($sendOtp)->setNext($logAttempt);

        return new ForgotPasswordChain($findUser);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
