<?php

declare(strict_types=1);

namespace App\Message;

use App\Repository\UserRepository;
use App\Service\Anime\SyncUserList;
use App\Service\Anime\SyncUserListData;
use App\Service\Shikimori\TokenStorage;
use App\Shikimori\Api\User\AnimeRatesRequest;
use App\Shikimori\Client\Shikimori;
use RuntimeException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class SyncListHandler
{
    private UserRepository $users;
    private TokenStorage $tokenStorage;
    private Shikimori $shikimori;
    private SyncUserList $syncUserList;

    public function __construct(
        UserRepository $users,
        TokenStorage $tokenStorage,
        Shikimori $shikimori,
        SyncUserList $syncUserList,
    ) {
        $this->users = $users;
        $this->tokenStorage = $tokenStorage;
        $this->shikimori = $shikimori;
        $this->syncUserList = $syncUserList;
    }

    public function __invoke(SyncList $message): void
    {
        $user = $this->users->find($message->userId);
        if (null === $user) {
            return;
        }

        $accountId = $user->getAccountId();
        if (null === $accountId) {
            throw new RuntimeException(sprintf('Oh no, user %s has no account id', $user->getId()));
        }

        $token = $this->tokenStorage->retrieve($user);
        $request = new AnimeRatesRequest($token, $accountId);
        $rates = $this->shikimori->request($request);

        ($this->syncUserList)(new SyncUserListData($user, $rates));
    }
}
