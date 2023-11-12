<?php

declare(strict_types=1);

namespace App\Message;

use App\Repository\UserRepository;
use App\Service\Sync\SyncUserAnimeRates;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class SyncUserAnimeRatesHandler
{
    private UserRepository $users;
    private SyncUserAnimeRates $syncUserAnimeRates;

    public function __construct(
        UserRepository $users,
        SyncUserAnimeRates $syncUserAnimeRates,
    ) {
        $this->users = $users;
        $this->syncUserAnimeRates = $syncUserAnimeRates;
    }

    public function __invoke(SyncUserAnimeRatesMessage $message): void
    {
        $user = $this->users->find($message->userId);
        if (null === $user) {
            return;
        }

        ($this->syncUserAnimeRates)($user);
    }
}
