<?php

declare(strict_types=1);

namespace App\Shikimori\Api\User;

use App\Shikimori\Api\Enum\UserAnimeStatus;

final readonly class AnimeRatesResponse
{
    public function __construct(
        public int $id,
        public int $score,
        public UserAnimeStatus $status,
        public AnimeRatesResponseAnimeItem $anime,
    ) {
    }
}
