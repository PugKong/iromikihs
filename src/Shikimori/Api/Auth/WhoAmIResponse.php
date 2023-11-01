<?php

declare(strict_types=1);

namespace App\Shikimori\Api\Auth;

final readonly class WhoAmIResponse
{
    public function __construct(public int $id)
    {
    }
}
