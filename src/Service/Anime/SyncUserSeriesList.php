<?php

declare(strict_types=1);

namespace App\Service\Anime;

use App\Entity\SeriesState;
use App\Entity\User;
use App\Repository\AnimeRateRepository;
use App\Repository\AnimeRepository;
use App\Repository\SeriesRateRepository;
use App\Repository\SeriesRepository;
use App\Shikimori\Api\Enum\Status;
use App\Shikimori\Api\Enum\UserAnimeStatus;

use function in_array;

final readonly class SyncUserSeriesList
{
    private AnimeRateRepository $animeRates;
    private SeriesRepository $series;
    private AnimeRepository $animes;
    private SeriesRateRepository $seriesRates;

    public function __construct(
        AnimeRateRepository $animeRates,
        SeriesRepository $series,
        AnimeRepository $animes,
        SeriesRateRepository $seriesRates,
    ) {
        $this->animeRates = $animeRates;
        $this->series = $series;
        $this->animes = $animes;
        $this->seriesRates = $seriesRates;
    }

    public function __invoke(User $user): void
    {
        $seriesIds = $this->animeRates->findSeriesIdsByUser($user);
        $syncSeries = $this->series->findBy(['id' => $seriesIds]);

        $saveUserSeries = [];
        foreach ($syncSeries as $series) {
            $releasedCount = $this->animes->count(['series' => $series, 'status' => Status::RELEASED]);
            if ($releasedCount <= 1) {
                continue;
            }

            $animes = $this->animes->findBy(['series' => $series]);
            $rates = $this->animeRates->findBy(['user' => $user, 'anime' => $animes]);

            $completedOrWatchingCount = 0;
            $droppedCount = 0;
            $scoreSum = 0;
            $scoreCount = 0;
            foreach ($rates as $rate) {
                if (in_array($rate->getStatus(), [
                    UserAnimeStatus::COMPLETED,
                    UserAnimeStatus::WATCHING,
                    UserAnimeStatus::REWATCHING,
                ])) {
                    ++$completedOrWatchingCount;
                }
                if (UserAnimeStatus::DROPPED === $rate->getStatus()) {
                    ++$droppedCount;
                }
                if (0 !== $rate->getScore()) {
                    ++$scoreCount;
                    $scoreSum += $rate->getScore();
                }
            }

            if (0 === $completedOrWatchingCount) {
                continue;
            }

            $userSeries = $this->seriesRates->findOrNew($user, $series);

            if (0 === $scoreCount) {
                $userSeries->setScore(0);
            } else {
                $userSeries->setScore($scoreSum / $scoreCount);
            }

            if ($releasedCount === ($completedOrWatchingCount + $droppedCount)) {
                $userSeries->setState(SeriesState::COMPLETE);
            } else {
                $userSeries->setState(SeriesState::INCOMPLETE);
            }

            $saveUserSeries[] = $userSeries;
        }

        $this->seriesRates->save(...$saveUserSeries);
    }
}
