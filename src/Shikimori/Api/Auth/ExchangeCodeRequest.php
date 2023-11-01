<?php

declare(strict_types=1);

namespace App\Shikimori\Api\Auth;

use App\Shikimori\Client\Config;
use App\Shikimori\Client\FormRequest;
use App\Shikimori\Client\Request;

/**
 * @implements Request<TokenResponse>
 */
final readonly class ExchangeCodeRequest implements Request, FormRequest
{
    public function __construct(private string $code)
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
            'grant_type' => 'authorization_code',
            'code' => $this->code,
            'client_id' => $config->clientId,
            'client_secret' => $config->clientSecret,
            'redirect_uri' => $config->redirectUrl(),
        ];
    }
}
