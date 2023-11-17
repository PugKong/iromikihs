<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\AnimeRateStatus;
use App\Shikimori\Api\Enum\Kind;
use App\Shikimori\Api\Enum\Status;

final readonly class UserRatedAnime
{
    public function __construct(
        public string $animeName,
        public Kind $animeKind,
        public Status $animeStatus,
        public string $animeUrl,
        public AnimeRateStatus $rateStatus,
        public int $score,
    ) {
    }
}
