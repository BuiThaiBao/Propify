<?php

namespace Tests\Unit\User;

use App\Commands\User\UpdateUserProfileCommand;
use App\DTOs\User\UpdateProfileDto;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Log;
use Mockery;
use PHPUnit\Framework\TestCase;

final class UpdateUserProfileCommandTest extends TestCase
{
    public function test_it_updates_profile_and_logs_changed_fields(): void
    {
        Log::spy();

        $user = new User([
            'full_name' => 'Old Name',
            'phone' => null,
            'avatar_url' => null,
        ]);
        $user->id = 10;

        $updatedUser = clone $user;
        $updatedUser->full_name = 'New Name';
        $updatedUser->phone = '0123456789';

        $repository = Mockery::mock(UserRepository::class);
        $repository
            ->shouldReceive('update')
            ->once()
            ->with(10, [
                'full_name' => 'New Name',
                'phone' => '0123456789',
            ])
            ->andReturn($updatedUser);

        $command = new UpdateUserProfileCommand($repository);

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
            ->with('User profile updated', Mockery::on(function (array $context): bool {
                return $context['user_id'] === 10
                    && $context['changed_fields'] === ['full_name', 'phone']
                    && $context['changes'] === [
                        'full_name' => ['old' => 'Old Name', 'new' => 'New Name'],
                        'phone' => ['old' => null, 'new' => '0123456789'],
                    ];
            }));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
