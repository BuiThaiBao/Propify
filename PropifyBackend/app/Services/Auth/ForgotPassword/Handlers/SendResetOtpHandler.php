<?php

namespace App\Services\Auth\ForgotPassword\Handlers;

use App\DTOs\Auth\ForgotPasswordContext;
use App\Enums\OtpContext;
use App\Services\Auth\ForgotPassword\AbstractForgotPasswordHandler;
use App\Services\Otp\OtpService;

final class SendResetOtpHandler extends AbstractForgotPasswordHandler
{
    public function __construct(
        private readonly OtpService $otpService,
    ) {
    }

    public function handle(ForgotPasswordContext $context): void
    {
        if ($context->user) {
            $this->otpService->generate($context->user, OtpContext::RESET_PASSWORD);
        }

        $this->next($context);
    }
}
