<?php

declare(strict_types=1);

namespace App\Service\Anime;

use App\Entity\User;
use App\Shikimori\Api\User\AnimeRatesResponse;

final class SyncUserListData
{
    public function __construct(
        public User $user,
        /** @var AnimeRatesResponse[] */
        public array $rates,
    ) {
    }
}
