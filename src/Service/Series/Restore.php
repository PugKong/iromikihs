<?php

declare(strict_types=1);

namespace App\Service\Series;

use App\Entity\SeriesRate;
use App\Entity\User;
use App\Exception\UserHasSyncInProgressException;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;

final readonly class Restore
{
    private RateCalculator $rateCalculator;
    private EntityManagerInterface $entityManager;

    public function __construct(RateCalculator $rateCalculator, EntityManagerInterface $entityManager)
    {
        $this->rateCalculator = $rateCalculator;
        $this->entityManager = $entityManager;
    }

    /**
     * @throws UserHasSyncInProgressException
     */
    public function __invoke(User $user, SeriesRate $seriesRate): void
    {
        $this->entityManager->wrapInTransaction(
            function (EntityManagerInterface $entityManager) use ($user, $seriesRate): void {
                $sync = $user->getSync();
                $entityManager->lock($sync, LockMode::PESSIMISTIC_WRITE);
                $entityManager->refresh($sync);
                if ($sync->isInProgress()) {
                    throw UserHasSyncInProgressException::create($user);
                }

                $calculation = ($this->rateCalculator)($user, $seriesRate->getSeries());

                $seriesRate->setState($calculation->state);
                $entityManager->persist($seriesRate);
            },
        );
    }
}
