<?php

namespace Tests;

use App\Services\Otp\Adapters\CacheOtpStorageAdapter;
use App\Services\Otp\OtpStoragePort;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app->bind(OtpStoragePort::class, CacheOtpStorageAdapter::class);
    }
}
