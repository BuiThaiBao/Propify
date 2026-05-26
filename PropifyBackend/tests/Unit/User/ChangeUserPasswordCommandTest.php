<?php

namespace Tests\Unit\User;

use App\Commands\User\ChangeUserPasswordCommand;
use App\DTOs\User\ChangePasswordDto;
use App\Enums\ErrorCode;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Exceptions\BusinessException;
use App\Models\AuditLog;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Services\User\Validation\PasswordValidationChain;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

final class ChangeUserPasswordCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_changes_password_and_creates_audit_log(): void
    {
        Log::spy();

        $user = User::create([
            'full_name' => 'User',
            'email' => 'user@example.com',
            'password' => Hash::make('OldPassword123'),
            'role' => UserRole::User->value,
            'status' => UserStatus::Active->value,
        ]);

        $repository = Mockery::mock(UserRepository::class);
        $repository
            ->shouldReceive('update')
            ->once()
            ->with($user->id, Mockery::on(function (array $data): bool {
                return isset($data['password'])
                    && Hash::check('NewPassword123', $data['password']);
            }))
            ->andReturn($user);

        $command = new ChangeUserPasswordCommand($repository, new PasswordValidationChain());
        $command->execute($user, new ChangePasswordDto('OldPassword123', 'NewPassword123'));

        $auditLog = AuditLog::query()->firstOrFail();

        $this->assertSame($user->id, $auditLog->actor_id);
        $this->assertSame(User::class, $auditLog->auditable_type);
        $this->assertSame($user->id, $auditLog->auditable_id);
        $this->assertSame('user.password.changed', $auditLog->action);
        $this->assertSame([], $auditLog->changes);
        $this->assertSame(['changed_fields' => ['password']], $auditLog->metadata);

        Log::shouldHaveReceived('info')
            ->once()
            ->with('User password changed', ['user_id' => $user->id]);
    }

    public function test_it_rejects_incorrect_current_password(): void
    {
        $user = User::create([
            'full_name' => 'User',
            'email' => 'user@example.com',
            'password' => Hash::make('OldPassword123'),
            'role' => UserRole::User->value,
            'status' => UserStatus::Active->value,
        ]);

        $repository = Mockery::mock(UserRepository::class);
        $repository->shouldNotReceive('update');

        $command = new ChangeUserPasswordCommand($repository, new PasswordValidationChain());

        try {
            $command->execute($user, new ChangePasswordDto('WrongPassword123', 'NewPassword123'));
            $this->fail('Expected BusinessException was not thrown.');
        } catch (BusinessException $exception) {
            $this->assertSame(ErrorCode::AuthPasswordIncorrect, $exception->getErrorCode());
        }

        $this->assertDatabaseCount('audit_logs', 0);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
