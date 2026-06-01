<?php

namespace App\Services\Auth;

use App\Enums\AuthMethod;
use LogicException;

final class AuthStrategyResolver
{
    /**
     * @param  AuthStrategy[]  $strategies
     */
    public function __construct(
        private readonly array $strategies,
    ) {}

    public function resolve(AuthMethod $method): AuthStrategy
    {
        foreach ($this->strategies as $strategy) {
            if ($strategy->method() === $method) {
                return $strategy;
            }
        }

        throw new LogicException("No auth strategy registered for method [{$method->value}].");
    }
}
