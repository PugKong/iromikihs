<?php

declare(strict_types=1);

namespace App\Service\Shikimori;

final readonly class TokenData
{
    public function __construct(
        public string $accessToken,
        public string $refreshToken,
        public int $expiresAt,
    ) {
    }
}
