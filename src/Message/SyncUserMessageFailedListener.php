<?php

declare(strict_types=1);

namespace App\Message;

use App\Entity\UserSyncState;
use App\Repository\UserSyncRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;

#[AsEventListener]
class SyncUserMessageFailedListener
{
    private UserSyncRepository $syncs;
    private EntityManagerInterface $entityManager;

    public function __construct(UserSyncRepository $syncs, EntityManagerInterface $entityManager)
    {
        $this->syncs = $syncs;
        $this->entityManager = $entityManager;
    }

    public function __invoke(WorkerMessageFailedEvent $event): void
    {
        $message = $event->getEnvelope()->getMessage();
        if (!$message instanceof SyncUserMessage) {
            return;
        }

        if ($event->willRetry()) {
            return;
        }

        $sync = $this->syncs->find($message->userId);
        if (null === $sync) {
            return;
        }

        $sync->setState(UserSyncState::FAILED);
        $this->entityManager->persist($sync);
        $this->entityManager->flush();
    }
}
