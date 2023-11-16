<?php

declare(strict_types=1);

namespace App\Service\Anime;

use App\Entity\Anime;
use App\Entity\AnimeRate;
use App\Entity\AnimeRateStatus;
use App\Entity\SeriesState;
use App\Entity\User;
use App\Repository\AnimeRateRepository;
use App\Repository\SeriesRateRepository;
use App\Service\Exception\AnimeHasNoSeriesException;
use App\Service\Exception\UserAnimeSeriesIsNotRatedException;
use App\Service\Exception\UserCantSkipAnimeException;
use App\Service\Exception\UserHasSyncInProgressException;
use App\Service\Series\RateCalculator;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;

final readonly class Skip
{
    private EntityManagerInterface $entityManager;
    private AnimeRateRepository $animeRates;
    private SeriesRateRepository $seriesRates;
    private RateCalculator $seriesRateCalculator;

    public function __construct(
        EntityManagerInterface $entityManager,
        AnimeRateRepository $animeRates,
        SeriesRateRepository $seriesRates,
        RateCalculator $seriesRateCalculator,
    ) {
        $this->seriesRateCalculator = $seriesRateCalculator;
        $this->animeRates = $animeRates;
        $this->seriesRates = $seriesRates;
        $this->entityManager = $entityManager;
    }

    /**
     * @throws UserHasSyncInProgressException
     * @throws UserCantSkipAnimeException
     * @throws AnimeHasNoSeriesException
     * @throws UserAnimeSeriesIsNotRatedException
     */
    public function __invoke(User $user, Anime $anime): void
    {
        $this->entityManager->wrapInTransaction(
            function (EntityManagerInterface $entityManager) use ($user, $anime): void {
                $sync = $user->getSync();
                $entityManager->lock($sync, LockMode::PESSIMISTIC_WRITE);
                $entityManager->refresh($sync);
                if ($sync->isInProgress()) {
                    throw UserHasSyncInProgressException::create($user);
                }

                $animeRate = $this->animeRates->findOneBy(['user' => $user, 'anime' => $anime]);
                if (null !== $animeRate) {
                    throw UserCantSkipAnimeException::create($user, $anime);
                }

                $series = $anime->getSeries();
                if (null === $series) {
                    throw AnimeHasNoSeriesException::create($anime);
                }

                $seriesRate = $this->seriesRates->findOneBy(['user' => $user, 'series' => $series]);
                if (null === $seriesRate) {
                    throw UserAnimeSeriesIsNotRatedException::create($user, $series);
                }

                $animeRate = new AnimeRate();
                $animeRate->setUser($user);
                $animeRate->setAnime($anime);
                $animeRate->setStatus(AnimeRateStatus::SKIPPED);
                $entityManager->persist($animeRate);
                $entityManager->flush();

                $calculation = ($this->seriesRateCalculator)($user, $series);

                $seriesRate->setScore($calculation->score);
                if (SeriesState::DROPPED !== $seriesRate->getState()) {
                    $seriesRate->setState($calculation->state);
                }
                $entityManager->persist($seriesRate);
                $entityManager->flush();
            },
        );
    }
}
