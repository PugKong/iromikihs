<?php

declare(strict_types=1);

namespace App\Service\Anime\GetUserSeriesList;

use App\Entity\AnimeRateStatus;
use App\Shikimori\Api\Enum\Kind;
use App\Shikimori\Api\Enum\Status;

final readonly class AnimeResult
{
    public function __construct(
        public int $id,
        public ?Kind $kind,
        public Status $status,
        public string $name,
        public string $url,
        public ?AnimeRateStatus $state,
        public ?int $score,
    ) {
    }
}
