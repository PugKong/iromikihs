<?php

declare(strict_types=1);

namespace App\Service\Shikimori;

use App\Shikimori\Api\BaseAnimeData;

final readonly class AnimeSeriesFetcherResult
{
    public function __construct(
        public string $seriesName,
        /** @var BaseAnimeData[] */
        public array $animes,
    ) {
    }
}
