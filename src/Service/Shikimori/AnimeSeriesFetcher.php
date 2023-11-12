<?php

declare(strict_types=1);

namespace App\Service\Shikimori;

use App\Entity\User;

interface AnimeSeriesFetcher
{
    public function __invoke(User $user, int $animeId): AnimeSeriesFetcherResult;
}
