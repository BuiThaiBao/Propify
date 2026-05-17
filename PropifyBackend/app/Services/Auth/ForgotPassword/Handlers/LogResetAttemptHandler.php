<?php

namespace App\Services\Auth\ForgotPassword\Handlers;

use App\DTOs\Auth\ForgotPasswordContext;
use App\Services\Auth\ForgotPassword\AbstractForgotPasswordHandler;
use Illuminate\Support\Facades\Log;

final class LogResetAttemptHandler extends AbstractForgotPasswordHandler
{
    public function handle(ForgotPasswordContext $context): void
    {
        if ($context->user) {
            Log::info('Password reset OTP sent', ['user_id' => $context->user->id]);
        }

        $this->next($context);
    }
}
