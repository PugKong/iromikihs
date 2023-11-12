<?php

declare(strict_types=1);

namespace App\Message;

use App\Repository\UserRepository;
use App\Service\Sync\SyncUserSeries;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class SyncUserSeriesHandler
{
    private UserRepository $users;
    private SyncUserSeries $syncUserSeries;

    public function __construct(UserRepository $users, SyncUserSeries $syncUserSeries)
    {
        $this->users = $users;
        $this->syncUserSeries = $syncUserSeries;
    }

    public function __invoke(SyncUserSeriesMessage $message): void
    {
        $user = $this->users->find($message->userId);
        if (null === $user) {
            return;
        }

        ($this->syncUserSeries)($user);
    }
}
