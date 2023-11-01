<?php

declare(strict_types=1);

namespace App\Shikimori\Client;

interface AuthenticatedRequest
{
    public function token(): string;
}
