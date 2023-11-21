<?php

declare(strict_types=1);

namespace App\Service\Series;

use App\Entity\SeriesRate;
use App\Entity\SeriesState;
use App\Entity\User;
use App\Exception\UserHasSyncInProgressException;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;

final readonly class Drop
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @throws UserHasSyncInProgressException
     */
    public function __invoke(User $user, SeriesRate $seriesRate): void
    {
        $this->entityManager->wrapInTransaction(fn () => $this->drop($user, $seriesRate));
    }

    /**
     * @throws UserHasSyncInProgressException
     */
    private function drop(User $user, SeriesRate $seriesRate): void
    {
        $sync = $user->getSync();
        $this->entityManager->lock($sync, LockMode::PESSIMISTIC_WRITE);
        $this->entityManager->refresh($sync);
        $sync->ensureNoActiveSync();

        $seriesRate->setState(SeriesState::DROPPED);
        $this->entityManager->persist($seriesRate);
        $this->entityManager->flush();
    }
}
