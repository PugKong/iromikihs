<?php

declare(strict_types=1);

namespace App\Tests\Shikimori\Client\RequestStub;

final readonly class ResponseStub
{
    public function __construct(public int $id)
    {
    }
}
