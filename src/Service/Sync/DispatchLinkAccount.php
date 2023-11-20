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
        $this->entityManager->wrapInTransaction(
            function (EntityManagerInterface $entityManager) use ($user, $code): void {
                $sync = $user->getSync();
                $entityManager->lock($sync, LockMode::PESSIMISTIC_WRITE);

                $entityManager->refresh($user);
                if ($sync->isLinked()) {
                    throw UserHasLinkedAccountException::create($user);
                }

                $entityManager->refresh($sync);
                if ($sync->isInProgress()) {
                    throw UserHasSyncInProgressException::create($user);
                }

                $sync->setState(UserSyncState::LINK_ACCOUNT);

                $entityManager->persist($sync);
                $entityManager->flush();

                $this->bus->dispatch(new LinkAccountMessage($user->getId(), $code));
            },
        );
    }
}
