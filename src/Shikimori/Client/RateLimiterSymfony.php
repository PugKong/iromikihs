<?php

declare(strict_types=1);

namespace App\Shikimori\Client;

use Symfony\Component\RateLimiter\RateLimiterFactory;

final readonly class RateLimiterSymfony implements RateLimiter
{
    private RateLimiterFactory $limiterFactory;

    public function __construct(RateLimiterFactory $shikimoriLimiter)
    {
        $this->limiterFactory = $shikimoriLimiter;
    }

    public function wait(): void
    {
        $this->limiterFactory->create()->reserve()->wait();
    }
}
