<?php

declare(strict_types=1);

namespace App\Tests\TestDouble\Shikimori;

use App\Shikimori\Client\RateLimiter;
use PHPUnit\Framework\Assert;

final class RateLimiterSpy implements RateLimiter
{
    private int $waited = 0;

    public function wait(): void
    {
        ++$this->waited;
    }

    public function assertWaited(int $expected): void
    {
        Assert::assertSame($expected, $this->waited);
    }
}
