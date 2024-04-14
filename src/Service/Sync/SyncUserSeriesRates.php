<?php

declare(strict_types=1);

namespace App\Service\Sync;

use App\Entity\User;
use App\Entity\UserSyncState;
use App\Repository\AnimeRateRepository;
use App\Repository\SeriesRateRepository;
use App\Repository\SeriesRepository;
use App\Service\Series\RateCalculator;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;

final readonly class SyncUserSeriesRates
{
    private AnimeRateRepository $animeRates;
    private SeriesRepository $series;
    private SeriesRateRepository $seriesRates;
    private RateCalculator $rateCalculator;
    private EntityManagerInterface $entityManager;
    private ClockInterface $clock;

    public function __construct(
        AnimeRateRepository $animeRates,
        SeriesRepository $series,
        SeriesRateRepository $seriesRates,
        RateCalculator $rateCalculator,
        EntityManagerInterface $entityManager,
        ClockInterface $clock,
    ) {
        $this->animeRates = $animeRates;
        $this->series = $series;
        $this->seriesRates = $seriesRates;
        $this->rateCalculator = $rateCalculator;
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

        $createdOrUpdatedRates = [];
        foreach ($syncSeries as $series) {
            $calculation = ($this->rateCalculator)($user, $series);
            if ($calculation->releasedCount <= 1) {
                continue;
            }

            if ($calculation->ratesCount == $calculation->droppedCount) {
                continue;
            }

            $userSeries = $this->seriesRates->findOrNew($user, $series);
            $userSeries->setScore($calculation->score);
            if (!$userSeries->isDropped()) {
                $userSeries->setState($calculation->state);
            }

            $createdOrUpdatedRates[] = $userSeries;
            $this->entityManager->persist($userSeries);
        }

        $orphanedRates = $this->seriesRates->findOtherByUser($user, $createdOrUpdatedRates);
        foreach ($orphanedRates as $orphanedRate) {
            $this->entityManager->remove($orphanedRate);
        }

        $sync->setState(null);
        $sync->setSyncedAt($this->clock->now());
        $this->entityManager->flush();
    }
}
