<?php

declare(strict_types=1);

namespace App\Message;

use App\Repository\UserRepository;
use App\Service\Sync\LinkAccount;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class LinkAccountHandler
{
    private UserRepository $users;
    private LinkAccount $linkAccount;

    public function __construct(
        UserRepository $users,
        LinkAccount $linkAccount,
    ) {
        $this->users = $users;
        $this->linkAccount = $linkAccount;
    }

    public function __invoke(LinkAccountMessage $message): void
    {
        $user = $this->users->find($message->userId);
        if (null === $user) {
            return;
        }

        ($this->linkAccount)($user, $message->code);
    }
}
