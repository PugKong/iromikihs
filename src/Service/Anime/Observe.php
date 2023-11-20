<?php

declare(strict_types=1);

namespace App\Service\Anime;

use App\Entity\Anime;
use App\Entity\AnimeRateStatus;
use App\Entity\SeriesRate;
use App\Entity\SeriesState;
use App\Entity\User;
use App\Exception\UserCantObserveAnimeException;
use App\Exception\UserHasSyncInProgressException;
use App\Repository\AnimeRateRepository;
use App\Service\Series\RateCalculator;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;

final readonly class Observe
{
    private EntityManagerInterface $entityManager;
    private AnimeRateRepository $animeRates;
    private RateCalculator $seriesRateCalculator;

    public function __construct(
        EntityManagerInterface $entityManager,
        AnimeRateRepository $animeRates,
        RateCalculator $seriesRateCalculator,
    ) {
        $this->seriesRateCalculator = $seriesRateCalculator;
        $this->animeRates = $animeRates;
        $this->entityManager = $entityManager;
    }

    /**
     * @throws UserHasSyncInProgressException
     * @throws UserCantObserveAnimeException
     */
    public function __invoke(User $user, SeriesRate $seriesRate, Anime $anime): void
    {
        $this->entityManager->wrapInTransaction(
            function (EntityManagerInterface $entityManager) use ($user, $seriesRate, $anime): void {
                $sync = $user->getSync();
                $entityManager->lock($sync, LockMode::PESSIMISTIC_WRITE);
                $entityManager->refresh($sync);
                if ($sync->isInProgress()) {
                    throw UserHasSyncInProgressException::create($user);
                }

                $animeRate = $this->animeRates->findOneBy([
                    'user' => $user,
                    'anime' => $anime,
                    'status' => AnimeRateStatus::SKIPPED,
                ]);
                if (null === $animeRate) {
                    throw UserCantObserveAnimeException::create($user, $anime);
                }
                $entityManager->remove($animeRate);
                $entityManager->flush();

                $series = $seriesRate->getSeries();
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
