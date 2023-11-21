<?php

declare(strict_types=1);

namespace App\Service\Sync;

use App\Entity\User;
use App\Entity\UserSyncState;
use App\Exception\UserHasLinkedAccountException;
use App\Exception\UserHasSyncInProgressException;
use App\Message\LinkAccountMessage;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final readonly class DispatchLinkAccount
{
    private EntityManagerInterface $entityManager;
    private MessageBusInterface $bus;

    public function __construct(EntityManagerInterface $entityManager, MessageBusInterface $bus)
    {
        $this->entityManager = $entityManager;
        $this->bus = $bus;
    }

    /**
     * @throws UserHasLinkedAccountException
     * @throws UserHasSyncInProgressException
     */
    public function __invoke(User $user, string $code): void
    {
        $this->entityManager->wrapInTransaction(fn () => $this->dispatch($user, $code));
    }

    /**
     * @throws UserHasLinkedAccountException
     * @throws UserHasSyncInProgressException
     */
    private function dispatch(User $user, string $code): void
    {
        $sync = $user->getSync();
        $this->entityManager->lock($sync, LockMode::PESSIMISTIC_WRITE);
        $this->entityManager->refresh($user);

        $sync->ensureNoActiveSync();
        if ($sync->isLinked()) {
            throw UserHasLinkedAccountException::create($user);
        }

        $sync->setState(UserSyncState::LINK_ACCOUNT);

        $this->entityManager->persist($sync);
        $this->entityManager->flush();

        $this->bus->dispatch(new LinkAccountMessage($user->getId(), $code));
    }
}
