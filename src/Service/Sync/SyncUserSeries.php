<?php

declare(strict_types=1);

namespace App\Service\Sync;

use App\Entity\Anime;
use App\Entity\Series;
use App\Entity\User;
use App\Entity\UserSync;
use App\Entity\UserSyncState;
use App\Message\SyncUserSeriesMessage;
use App\Message\SyncUserSeriesRatesMessage;
use App\Repository\AnimeRateRepository;
use App\Repository\AnimeRepository;
use App\Service\Shikimori\AnimeSeriesFetcher;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final readonly class SyncUserSeries
{
    private AnimeRateRepository $rates;
    private AnimeSeriesFetcher $seriesFetcher;
    private AnimeRepository $animes;
    private ClockInterface $clock;
    private MessageBusInterface $bus;
    private EntityManagerInterface $entityManager;

    public function __construct(
        AnimeRateRepository $rates,
        AnimeRepository $animes,
        AnimeSeriesFetcher $seriesFetcher,
        ClockInterface $clock,
        MessageBusInterface $bus,
        EntityManagerInterface $entityManager,
    ) {
        $this->rates = $rates;
        $this->animes = $animes;
        $this->seriesFetcher = $seriesFetcher;
        $this->clock = $clock;
        $this->bus = $bus;
        $this->entityManager = $entityManager;
    }

    public function __invoke(User $user): void
    {
        $sync = $user->getSync();
        if (!$sync->isLinked() || UserSyncState::SERIES !== $sync->getState()) {
            return;
        }

        $anime = $this->rates->findNextAnimeToSyncSeriesByUser($user);
        if (null === $anime) {
            $this->finish($user, $sync);

            return;
        }

        $this->sync($user, $anime);
    }

    private function finish(User $user, UserSync $sync): void
    {
        $sync->setState(UserSyncState::SERIES_RATES);
        $this->entityManager->persist($sync);
        $this->entityManager->flush();

        $this->bus->dispatch(new SyncUserSeriesRatesMessage($user->getId()));
    }

    private function sync(User $user, Anime $anime): void
    {
        $result = ($this->seriesFetcher)($user, $anime->getId());

        $series = null;
        $animes = [];
        foreach ($result->animes as $animeData) {
            $anime = $this->animes->findOrNew($animeData->id);
            if (null === $series && null !== $anime->getSeries()) {
                $series = $anime->getSeries();
            }
            $anime->updateFromBaseData($animeData);

            $animes[] = $anime;
        }

        if (null === $series) {
            $series = new Series();
        }

        foreach ($animes as $anime) {
            $anime->setSeries($series);
            $this->entityManager->persist($anime);
        }

        $series->setName($result->seriesName);
        $series->setUpdatedAt($this->clock->now());
        $this->entityManager->persist($series);
        $this->entityManager->flush();

        $this->bus->dispatch(new SyncUserSeriesMessage($user->getId()));
    }
}
