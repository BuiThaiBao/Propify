<?php

namespace App\Http\Middleware;

use App\Helpers\ApiResponse;
use App\Services\Auth\TokenProcessService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;

final class CheckBlackListToken
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function __construct(
        private readonly TokenProcessService $tokenProcessService
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $token = JWTAuth::getToken();

        if (!$token) {
            return ApiResponse::unauthorized('Không tìm thấy token');
        }

        if ($this->tokenProcessService->isBlacklist((string) $token)) {
            return ApiResponse::unauthorized('Token đã bị thu hồi hoặc hết hạn');
        }

        return $next($request);
    }
}
