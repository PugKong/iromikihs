<?php

declare(strict_types=1);

namespace App\Service\Anime;

use App\Entity\User;
use App\Entity\UserSeriesState;
use App\Repository\AnimeRateRepository;
use App\Repository\AnimeRepository;
use App\Repository\SeriesRepository;
use App\Repository\SeriesStateRepository;
use App\Shikimori\Api\Enum\Status;
use App\Shikimori\Api\Enum\UserAnimeStatus;

final readonly class SyncUserSeriesList
{
    private AnimeRateRepository $rates;
    private SeriesRepository $series;
    private AnimeRepository $animes;
    private SeriesStateRepository $userSeries;

    public function __construct(
        AnimeRateRepository $rates,
        SeriesRepository $series,
        AnimeRepository $animes,
        SeriesStateRepository $userSeries,
    ) {
        $this->rates = $rates;
        $this->series = $series;
        $this->animes = $animes;
        $this->userSeries = $userSeries;
    }

    public function __invoke(User $user): void
    {
        $seriesIds = $this->rates->findSeriesIdsByUser($user);
        $syncSeries = $this->series->findBy(['id' => $seriesIds]);

        $saveUserSeries = [];
        foreach ($syncSeries as $series) {
            $releasedCount = $this->animes->count(['series' => $series, 'status' => Status::RELEASED]);
            if ($releasedCount <= 1) {
                continue;
            }

            $completedOrWatchingCount = $this->rates->countByUserAndSeries(
                $user,
                $series,
                statuses: [
                    UserAnimeStatus::COMPLETED,
                    UserAnimeStatus::WATCHING,
                    UserAnimeStatus::REWATCHING,
                ],
            );
            if (0 === $completedOrWatchingCount) {
                continue;
            }

            $droppedCount = $this->rates->countByUserAndSeries($user, $series, statuses: [UserAnimeStatus::DROPPED]);
            $userSeries = $this->userSeries->findOrNew($user, $series);
            if ($releasedCount === ($completedOrWatchingCount + $droppedCount)) {
                $userSeries->setState(UserSeriesState::COMPLETE);
            } else {
                $userSeries->setState(UserSeriesState::INCOMPLETE);
            }

            $saveUserSeries[] = $userSeries;
        }

        $this->userSeries->save(...$saveUserSeries);
    }
}
