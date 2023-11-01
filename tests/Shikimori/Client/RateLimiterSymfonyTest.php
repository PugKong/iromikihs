<?php

declare(strict_types=1);

namespace App\Tests\Shikimori\Client;

use App\Shikimori\Client\RateLimiterSymfony;
use App\Tests\Trait\GetService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class RateLimiterSymfonyTest extends KernelTestCase
{
    use GetService;

    public function testWaitNoDelay(): void
    {
        $limiter = self::getService(RateLimiterSymfony::class);

        $start = microtime(true);
        $limiter->wait();
        $end = microtime(true);

        self::assertLessThanOrEqual(0.005, $end - $start);
    }

    /**
     * @group slow
     */
    public function testWaitDelay(): void
    {
        $limiter = self::getService(RateLimiterSymfony::class);

        $start = microtime(true);
        $limiter->wait();
        $limiter->wait();
        $end = microtime(true);

        self::assertGreaterThan(1.0, $end - $start);
        self::assertLessThan(2.0, $end - $start);
    }
}
