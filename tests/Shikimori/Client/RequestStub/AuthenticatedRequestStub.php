<?php

declare(strict_types=1);

namespace App\Tests\Shikimori\Client\RequestStub;

use App\Shikimori\Client\AuthenticatedRequest;

readonly class AuthenticatedRequestStub extends RequestStub implements AuthenticatedRequest
{
    public const TOKEN = '123';

    public function token(): string
    {
        return self::TOKEN;
    }
}
