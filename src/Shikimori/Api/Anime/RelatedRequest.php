<?php

declare(strict_types=1);

namespace App\Shikimori\Api\Anime;

use App\Shikimori\Client\AuthenticatedRequest;
use App\Shikimori\Client\ListRequest;
use App\Shikimori\Client\Request;

/**
 * @implements Request<RelatedResponse>
 */
final readonly class RelatedRequest implements Request, AuthenticatedRequest, ListRequest
{
    public function __construct(private string $token, private int $animeId)
    {
    }

    public function token(): string
    {
        return $this->token;
    }

    public function method(): string
    {
        return 'GET';
    }

    public function uri(): string
    {
        return sprintf('/api/animes/%d/related', $this->animeId);
    }

    public function responseClass(): string
    {
        return RelatedResponse::class;
    }
}
