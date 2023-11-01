<?php

declare(strict_types=1);

namespace App\Message;

use App\Repository\UserRepository;
use App\Service\Shikimori\TokenStorage;
use App\Shikimori\Api\Auth\ExchangeCodeRequest;
use App\Shikimori\Api\Auth\WhoAmIRequest;
use App\Shikimori\Client\Shikimori;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class LinkAccountHandler
{
    private Shikimori $shikimori;
    private UserRepository $users;
    private TokenStorage $tokenStorage;

    public function __construct(Shikimori $shikimori, UserRepository $users, TokenStorage $tokenStorage)
    {
        $this->shikimori = $shikimori;
        $this->users = $users;
        $this->tokenStorage = $tokenStorage;
    }

    public function __invoke(LinkAccount $message): void
    {
        $user = $this->users->find($message->userId);
        if (null === $user) {
            return;
        }

        $response = $this->shikimori->request(new ExchangeCodeRequest($message->code));
        $this->tokenStorage->store($user, $response);

        $response = $this->shikimori->request(new WhoAmIRequest($this->tokenStorage->retrieve($user)));
        $user->setAccountId($response->id);
        $this->users->save($user);
    }
}
