<?php

declare(strict_types=1);

namespace App\Tests\Message;

use App\Entity\UserSyncState;
use App\Message\SyncUserMessageFailedListener;
use App\Tests\Factory\UserFactory;
use RuntimeException;
use stdClass;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;
use Symfony\Component\Uid\Uuid;

final class SyncUserMessageFailedListenerTest extends MessageHandlerTestCase
{
    public function testMarkSyncFailed(): void
    {
        $user = UserFactory::createOne();
        $event = new WorkerMessageFailedEvent(
            new Envelope(new SyncUserMessageStub($user->getId())),
            'idk',
            new RuntimeException('Oh no, some error!'),
        );

        $listener = self::getService(SyncUserMessageFailedListener::class);
        ($listener)($event);

        self::assertSame(UserSyncState::FAILED, $user->getSync()->getState());
    }

    public function testDontMarkSyncFailedIfThereWillBeRetry(): void
    {
        $user = UserFactory::createOne();
        $event = new WorkerMessageFailedEvent(
            new Envelope(new SyncUserMessageStub($user->getId())),
            'idk',
            new RuntimeException('Oh no, some error!'),
        );
        $event->setForRetry();

        $listener = self::getService(SyncUserMessageFailedListener::class);
        ($listener)($event);

        self::assertNull($user->getSync()->getState());
    }

    public function testHandleUserNotFound(): void
    {
        $this->expectNotToPerformAssertions();

        $event = new WorkerMessageFailedEvent(
            new Envelope(new SyncUserMessageStub(Uuid::v7())),
            'idk',
            new RuntimeException('Oh no, some error!'),
        );

        $listener = self::getService(SyncUserMessageFailedListener::class);
        ($listener)($event);
    }

    public function testHandleOtherMessages(): void
    {
        $this->expectNotToPerformAssertions();

        $event = new WorkerMessageFailedEvent(
            new Envelope(new stdClass()),
            'idk',
            new RuntimeException('Oh no, some error!'),
        );

        $listener = self::getService(SyncUserMessageFailedListener::class);
        ($listener)($event);
    }
}
