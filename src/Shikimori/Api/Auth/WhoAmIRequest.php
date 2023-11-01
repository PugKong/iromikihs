<?php

declare(strict_types=1);

namespace App\Shikimori\Api\Auth;

use App\Shikimori\Client\AuthenticatedRequest;
use App\Shikimori\Client\Request;

/**
 * @implements Request<WhoAmIResponse>
 */
final readonly class WhoAmIRequest implements Request, AuthenticatedRequest
{
    public function __construct(private string $accessToken)
    {
    }

    public function method(): string
    {
        return 'GET';
    }

    public function uri(): string
    {
        return '/api/users/whoami';
    }

    public function responseClass(): string
    {
        return WhoAmIResponse::class;
    }

    public function token(): string
    {
        return $this->accessToken;
    }
}
