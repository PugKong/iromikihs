<?php

declare(strict_types=1);

namespace App\Service\Sync;

use App\Entity\User;
use App\Entity\UserSyncState;
use App\Service\Shikimori\TokenStorage;
use App\Shikimori\Api\Auth\ExchangeCodeRequest;
use App\Shikimori\Api\Auth\WhoAmIRequest;
use App\Shikimori\Client\Shikimori;
use Doctrine\ORM\EntityManagerInterface;

final readonly class LinkAccount
{
    private Shikimori $shikimori;
    private TokenStorage $tokenStorage;
    private EntityManagerInterface $entityManager;

    public function __construct(
        Shikimori $shikimori,
        TokenStorage $tokenStorage,
        EntityManagerInterface $entityManager,
    ) {
        $this->shikimori = $shikimori;
        $this->tokenStorage = $tokenStorage;
        $this->entityManager = $entityManager;
    }

    public function __invoke(User $user, string $code): void
    {
        $sync = $user->getSync();
        if ($sync->isLinked() || UserSyncState::LINK_ACCOUNT !== $sync->getState()) {
            return;
        }

        $response = $this->shikimori->request(new ExchangeCodeRequest($code));
        $this->tokenStorage->store($user, $response);

        $response = $this->shikimori->request(new WhoAmIRequest($this->tokenStorage->retrieve($user)));
        $sync->setAccountId($response->id);
        $sync->setState(null);
        $this->entityManager->persist($sync);

        $this->entityManager->flush();
    }
}
