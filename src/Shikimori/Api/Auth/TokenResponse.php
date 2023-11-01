<?php

declare(strict_types=1);

namespace App\Shikimori\Api\Auth;

use Symfony\Component\Serializer\Annotation\SerializedName;

final readonly class TokenResponse
{
    public function __construct(
        #[SerializedName('access_token')]
        public string $accessToken,
        #[SerializedName('refresh_token')]
        public string $refreshToken,
        #[SerializedName('created_at')]
        public int $createdAt,
        #[SerializedName('expires_in')]
        public int $expiresIn,
    ) {
    }

    public function expiresAt(): int
    {
        return $this->createdAt + $this->expiresIn;
    }
}
