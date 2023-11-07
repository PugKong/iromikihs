<?php

declare(strict_types=1);

namespace App\Shikimori\Api\Anime;

use App\Shikimori\Client\AuthenticatedRequest;
use App\Shikimori\Client\Request;

/**
 * @implements Request<ItemResponse>
 */
final readonly class ItemRequest implements Request, AuthenticatedRequest
{
    public function __construct(private string $token, private int $id)
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
        return sprintf('/api/animes/%d', $this->id);
    }

    public function responseClass(): string
    {
        return ItemResponse::class;
    }
}
