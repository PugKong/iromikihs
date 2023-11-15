<?php

declare(strict_types=1);

namespace App\Service\Series;

use App\Entity\SeriesRate;
use App\Entity\SeriesState;
use App\Entity\User;
use App\Service\Exception\UserHasSyncInProgressException;
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
        $this->entityManager->wrapInTransaction(
            function (EntityManagerInterface $entityManager) use ($user, $seriesRate): void {
                $sync = $user->getSync();
                $entityManager->lock($sync, LockMode::PESSIMISTIC_WRITE);
                $entityManager->refresh($sync);
                if ($sync->isInProgress()) {
                    throw UserHasSyncInProgressException::create($user);
                }

                $seriesRate->setState(SeriesState::DROPPED);
                $entityManager->persist($seriesRate);
            },
        );
    }
}
