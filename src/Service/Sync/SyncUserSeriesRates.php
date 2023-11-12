<?php

declare(strict_types=1);

namespace App\Service\Sync;

use App\Entity\SeriesState;
use App\Entity\User;
use App\Entity\UserSyncState;
use App\Repository\AnimeRateRepository;
use App\Repository\AnimeRepository;
use App\Repository\SeriesRateRepository;
use App\Repository\SeriesRepository;
use App\Shikimori\Api\Enum\Status;
use App\Shikimori\Api\Enum\UserAnimeStatus;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;

use function in_array;

final readonly class SyncUserSeriesRates
{
    private AnimeRateRepository $animeRates;
    private SeriesRepository $series;
    private AnimeRepository $animes;
    private SeriesRateRepository $seriesRates;
    private EntityManagerInterface $entityManager;
    private ClockInterface $clock;

    public function __construct(
        AnimeRateRepository $animeRates,
        SeriesRepository $series,
        AnimeRepository $animes,
        SeriesRateRepository $seriesRates,
        EntityManagerInterface $entityManager,
        ClockInterface $clock,
    ) {
        $this->animeRates = $animeRates;
        $this->series = $series;
        $this->animes = $animes;
        $this->seriesRates = $seriesRates;
        $this->entityManager = $entityManager;
        $this->clock = $clock;
    }

    public function __invoke(User $user): void
    {
        $sync = $user->getSync();
        if (!$sync->isLinked() || UserSyncState::SERIES_RATES !== $sync->getState()) {
            return;
        }

        $seriesIds = $this->animeRates->findSeriesIdsByUser($user);
        $syncSeries = $this->series->findBy(['id' => $seriesIds]);

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

            $this->entityManager->persist($userSeries);
        }

        $sync->setState(null);
        $sync->setSyncedAt($this->clock->now());
        $this->entityManager->flush();

        $this->entityManager->flush();
    }
}
