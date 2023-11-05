<?php

declare(strict_types=1);

namespace App\Shikimori\Api\User;

use App\Shikimori\Api\Enum\UserAnimeStatus;
use App\Shikimori\Client\AuthenticatedRequest;
use App\Shikimori\Client\ListRequest;
use App\Shikimori\Client\Request;

/**
 * @implements Request<AnimeRatesResponse>
 */
final readonly class AnimeRatesRequest implements Request, AuthenticatedRequest, ListRequest
{
    public function __construct(private string $token, private int $accountId, private ?UserAnimeStatus $status = null)
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
        $uri = sprintf('/api/users/%d/anime_rates?', $this->accountId);
        $query = ['limit' => 5000];
        if (null !== $this->status) {
            $query['status'] = $this->status->value;
        }

        return $uri.http_build_query($query);
    }

    public function responseClass(): string
    {
        return AnimeRatesResponse::class;
    }
}
