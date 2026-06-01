<?php

namespace App\Services\Auth\ForgotPassword\Handlers;

use App\DTOs\Auth\ForgotPasswordContext;
use App\Repositories\UserRepository;
use App\Services\Auth\ForgotPassword\AbstractForgotPasswordHandler;
use Illuminate\Support\Facades\Log;

final class FindResetUserHandler extends AbstractForgotPasswordHandler
{
    public function __construct(
        private readonly UserRepository $userRepository,
    ) {}

    public function handle(ForgotPasswordContext $context): void
    {
        $context->user = $this->userRepository->findByEmail($context->email);

        if (! $context->user) {
            Log::warning('Forgot password for unknown email', ['email' => $context->email]);

            return;
        }

        $this->next($context);
    }
}
