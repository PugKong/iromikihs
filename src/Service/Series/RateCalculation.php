<?php

declare(strict_types=1);

namespace App\Service\Series;

use App\Entity\SeriesState;

final readonly class RateCalculation
{
    public function __construct(
        public int $releasedCount,
        public int $ratesCount,
        public int $droppedCount,
        public SeriesState $state,
        public float $score,
    ) {
    }
}
