<?php

declare(strict_types=1);

namespace App\Shikimori\Api\Auth;

use App\Shikimori\Client\Config;
use App\Shikimori\Client\FormRequest;
use App\Shikimori\Client\Request;

/**
 * @implements Request<TokenResponse>
 */
final readonly class RefreshTokenRequest implements Request, FormRequest
{
    public function __construct(private string $refreshToken)
    {
    }

    public function method(): string
    {
        return 'POST';
    }

    public function uri(): string
    {
        return '/oauth/token';
    }

    public function responseClass(): string
    {
        return TokenResponse::class;
    }

    public function form(Config $config): array
    {
        return [
            'grant_type' => 'refresh_token',
            'client_id' => $config->clientId,
            'client_secret' => $config->clientSecret,
            'refresh_token' => $this->refreshToken,
        ];
    }
}
