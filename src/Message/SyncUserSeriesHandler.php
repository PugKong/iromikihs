<?php

declare(strict_types=1);

namespace App\Message;

use App\Repository\UserRepository;
use App\Service\Anime\SyncUserSeriesList;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class SyncUserSeriesHandler
{
    private UserRepository $users;
    private SyncUserSeriesList $syncUserSeries;

    public function __construct(UserRepository $users, SyncUserSeriesList $syncUserSeries)
    {
        $this->users = $users;
        $this->syncUserSeries = $syncUserSeries;
    }

    public function __invoke(SyncUserSeries $message): void
    {
        $user = $this->users->find($message->userId);
        if (null === $user) {
            return;
        }

        ($this->syncUserSeries)($user);
    }
}
