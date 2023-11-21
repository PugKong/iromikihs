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
        $this->entityManager->wrapInTransaction(fn () => $this->restore($user, $seriesRate));
    }

    /**
     * @throws UserHasSyncInProgressException
     */
    private function restore(User $user, SeriesRate $seriesRate): void
    {
        $sync = $user->getSync();
        $this->entityManager->lock($sync, LockMode::PESSIMISTIC_WRITE);
        $this->entityManager->refresh($sync);
        $sync->ensureNoActiveSync();

        $calculation = ($this->rateCalculator)($user, $seriesRate->getSeries());

        $seriesRate->setState($calculation->state);
        $this->entityManager->persist($seriesRate);
        $this->entityManager->flush();
    }
}
