<?php

declare(strict_types=1);

namespace App\Service\Sync;

use App\Entity\User;
use App\Entity\UserSyncState;
use App\Message\SyncUserAnimeRatesMessage;
use App\Service\Exception\UserHasNoLinkedAccountException;
use App\Service\Exception\UserHasSyncInProgressException;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final readonly class DispatchUserDataSync
{
    private EntityManagerInterface $entityManager;
    private MessageBusInterface $bus;

    public function __construct(EntityManagerInterface $entityManager, MessageBusInterface $bus)
    {
        $this->entityManager = $entityManager;
        $this->bus = $bus;
    }

    /**
     * @throws UserHasNoLinkedAccountException
     * @throws UserHasSyncInProgressException
     */
    public function __invoke(User $user): void
    {
        $this->entityManager->wrapInTransaction(function (EntityManagerInterface $entityManager) use ($user): void {
            $sync = $user->getSync();
            $entityManager->lock($sync, LockMode::PESSIMISTIC_WRITE);

            $entityManager->refresh($user);
            if (!$sync->isLinked()) {
                throw UserHasNoLinkedAccountException::create($user);
            }

            $entityManager->refresh($sync);
            if ($sync->isInProgress()) {
                throw UserHasSyncInProgressException::create($user);
            }

            $sync->setState(UserSyncState::ANIME_RATES);

            $entityManager->persist($sync);
            $entityManager->flush();

            $this->bus->dispatch(new SyncUserAnimeRatesMessage($user->getId()));
        });
    }
}
