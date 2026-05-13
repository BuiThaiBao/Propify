<?php

namespace App\Support;

use App\DTOs\Auth\AuthResultDto;
use Symfony\Component\HttpFoundation\Cookie;

final class AuthCookieFactory
{
    public const CLIENT_ADMIN = 'admin';
    public const CLIENT_USER = 'user';

    /**
     * @return array{0: Cookie, 1: Cookie}
     */
    public static function makeAuthCookies(AuthResultDto $result, string $client = self::CLIENT_USER): array
    {
        return [
            self::makeAccessCookie($result->accessToken, (int) ceil($result->expiresIn / 60), $client),
            self::makeRefreshCookie($result->refreshToken, $client),
        ];
    }

    public static function makeAccessCookie(string $token, int $ttlMinutes, string $client = self::CLIENT_USER): Cookie
    {
        return self::makeCookie(
            name: self::accessCookieName($client),
            value: $token,
            minutes: $ttlMinutes,
            httpOnly: false,
        );
    }

    public static function makeRefreshCookie(string $token, string $client = self::CLIENT_USER): Cookie
    {
        return self::makeCookie(
            name: self::refreshCookieName($client),
            value: $token,
            minutes: (int) config('jwt.refresh_ttl', 20160),
            httpOnly: true,
        );
    }

    /**
     * @return array{0: Cookie, 1: Cookie}
     */
    public static function forgetAuthCookies(string $client = self::CLIENT_USER): array
    {
        return [
            self::forgetCookie(self::accessCookieName($client)),
            self::forgetCookie(self::refreshCookieName($client)),
        ];
    }

    public static function accessCookieName(string $client = self::CLIENT_USER): string
    {
        if (self::normalizeClient($client) === self::CLIENT_ADMIN) {
            return (string) config('auth_cookies.admin.access_cookie', 'propify_admin_access_token');
        }

        return (string) config('auth_cookies.user.access_cookie', 'propify_user_access_token');
    }

    public static function refreshCookieName(string $client = self::CLIENT_USER): string
    {
        if (self::normalizeClient($client) === self::CLIENT_ADMIN) {
            return (string) config('auth_cookies.admin.refresh_cookie', 'propify_admin_refresh_token');
        }

        return (string) config('auth_cookies.user.refresh_cookie', 'propify_user_refresh_token');
    }

    public static function normalizeClient(?string $client): string
    {
        return strtolower((string) $client) === self::CLIENT_ADMIN
            ? self::CLIENT_ADMIN
            : self::CLIENT_USER;
    }

    private static function makeCookie(string $name, string $value, int $minutes, bool $httpOnly): Cookie
    {
        return cookie(
            name: $name,
            value: $value,
            minutes: $minutes,
            path: self::path(),
            domain: self::domain(),
            secure: self::secure(),
            httpOnly: $httpOnly,
            raw: false,
            sameSite: self::sameSite(),
        );
    }

    private static function forgetCookie(string $name): Cookie
    {
        return cookie()->forget(
            name: $name,
            path: self::path(),
            domain: self::domain(),
        );
    }

    private static function path(): string
    {
        return (string) config('auth_cookies.path', '/');
    }

    private static function domain(): ?string
    {
        $domain = config('auth_cookies.domain');
        return $domain === null || $domain === '' ? null : (string) $domain;
    }

    private static function secure(): bool
    {
        return filter_var(config('auth_cookies.secure', app()->environment('production')), FILTER_VALIDATE_BOOL);
    }

    private static function sameSite(): string
    {
        return (string) config('auth_cookies.same_site', 'Lax');
    }
}
