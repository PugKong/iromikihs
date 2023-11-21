<?php

declare(strict_types=1);

namespace App\Service\Sync;

use App\Entity\User;
use App\Entity\UserSyncState;
use App\Exception\UserHasNoLinkedAccountException;
use App\Exception\UserHasSyncInProgressException;
use App\Message\SyncUserAnimeRatesMessage;
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
        $this->entityManager->wrapInTransaction(fn () => $this->dispatch($user));
    }

    /**
     * @throws UserHasNoLinkedAccountException
     * @throws UserHasSyncInProgressException
     */
    private function dispatch(User $user): void
    {
        $sync = $user->getSync();
        $this->entityManager->lock($sync, LockMode::PESSIMISTIC_WRITE);
        $this->entityManager->refresh($user);

        $sync->ensureNoActiveSync();
        if (!$sync->isLinked()) {
            throw UserHasNoLinkedAccountException::create($user);
        }

        $sync->setState(UserSyncState::ANIME_RATES);

        $this->entityManager->persist($sync);
        $this->entityManager->flush();

        $this->bus->dispatch(new SyncUserAnimeRatesMessage($user->getId()));
    }
}
