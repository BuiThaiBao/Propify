<?php

namespace Tests\Unit\Auth;

use App\DTOs\Auth\AuthPayload;
use App\DTOs\Auth\AuthResultDto;
use App\Enums\AuthMethod;
use App\Services\Auth\AuthStrategy;
use App\Services\Auth\AuthStrategyResolver;
use PHPUnit\Framework\TestCase;

final class AuthStrategyResolverTest extends TestCase
{
    public function test_it_resolves_the_strategy_matching_the_requested_method(): void
    {
        $emailStrategy = new class implements AuthStrategy {
            public function method(): AuthMethod
            {
                return AuthMethod::EmailPassword;
            }

            public function authenticate(AuthPayload $payload): AuthResultDto
            {
                throw new \LogicException('Not needed for this test.');
            }
        };

        $googleStrategy = new class implements AuthStrategy {
            public function method(): AuthMethod
            {
                return AuthMethod::GoogleOAuth;
            }

            public function authenticate(AuthPayload $payload): AuthResultDto
            {
                throw new \LogicException('Not needed for this test.');
            }
        };

        $resolver = new AuthStrategyResolver([$emailStrategy, $googleStrategy]);

        $this->assertSame($googleStrategy, $resolver->resolve(AuthMethod::GoogleOAuth));
    }
}
