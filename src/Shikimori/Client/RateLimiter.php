<?php

declare(strict_types=1);

namespace App\Shikimori\Client;

interface RateLimiter
{
    public function wait(): void;
}
