<?php

declare(strict_types=1);

namespace App\Message;

use App\Repository\UserRepository;
use App\Service\Sync\SyncUserSeriesRates;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class SyncUserSeriesRatesHandler
{
    private UserRepository $users;
    private SyncUserSeriesRates $syncUserSeriesRates;

    public function __construct(UserRepository $users, SyncUserSeriesRates $syncUserSeriesRates)
    {
        $this->users = $users;
        $this->syncUserSeriesRates = $syncUserSeriesRates;
    }

    public function __invoke(SyncUserSeriesRatesMessage $message): void
    {
        $user = $this->users->find($message->userId);
        if (null === $user) {
            return;
        }

        ($this->syncUserSeriesRates)($user);
    }
}
