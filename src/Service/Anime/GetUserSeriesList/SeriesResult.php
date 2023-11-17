<?php

declare(strict_types=1);

namespace App\Service\Anime\GetUserSeriesList;

use App\Entity\SeriesState;

final class SeriesResult
{
    public function __construct(
        public string $id,
        public string $name,
        public string $seriesRateId,
        public SeriesState $state,
        public float $score,
        /** @var AnimeResult[] */
        public array $animes = [],
    ) {
    }
}
