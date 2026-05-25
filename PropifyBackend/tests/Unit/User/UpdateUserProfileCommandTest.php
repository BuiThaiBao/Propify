<?php

namespace Tests\Unit\User;

use App\Commands\User\UpdateUserProfileCommand;
use App\DTOs\User\UpdateProfileDto;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\AuditLog;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Services\User\Validation\ProfileValidationChain;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

final class UpdateUserProfileCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_updates_profile_and_logs_changed_fields(): void
    {
        Log::spy();

        $user = User::create([
            'full_name' => 'Old Name',
            'email' => 'old@example.com',
            'password' => 'Password123',
            'role' => UserRole::User->value,
            'status' => UserStatus::Active->value,
            'phone' => null,
            'avatar_url' => null,
        ]);

        $updatedUser = clone $user;
        $updatedUser->full_name = 'New Name';
        $updatedUser->phone = '0123456789';

        $repository = Mockery::mock(UserRepository::class);
        $repository
            ->shouldReceive('update')
            ->once()
            ->with($user->id, [
                'full_name' => 'New Name',
                'phone' => '0123456789',
            ])
            ->andReturn($updatedUser);

        $command = new UpdateUserProfileCommand($repository, new ProfileValidationChain());

        $result = $command->execute(
            $user,
            new UpdateProfileDto(
                fullName: 'New Name',
                phone: '0123456789',
            ),
        );

        $this->assertSame($updatedUser, $result);

        Log::shouldHaveReceived('info')
            ->once()
            ->with('User profile updated', Mockery::on(function (array $context) use ($user): bool {
                return $context['user_id'] === $user->id
                    && $context['changed_fields'] === ['full_name', 'phone']
                    && $context['changes'] === [
                        'full_name' => ['old' => 'Old Name', 'new' => 'New Name'],
                        'phone' => ['old' => null, 'new' => '0123456789'],
                    ];
            }));

        $auditLog = AuditLog::query()->firstOrFail();

        $this->assertSame($user->id, $auditLog->actor_id);
        $this->assertSame(User::class, $auditLog->auditable_type);
        $this->assertSame($user->id, $auditLog->auditable_id);
        $this->assertSame('user.profile.updated', $auditLog->action);
        $this->assertSame([
            'full_name' => ['old' => 'Old Name', 'new' => 'New Name'],
            'phone' => ['old' => null, 'new' => '0123456789'],
        ], $auditLog->changes);
        $this->assertSame([
            'changed_fields' => ['full_name', 'phone'],
        ], $auditLog->metadata);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
