<?php

declare(strict_types=1);

namespace App\Message;

use Symfony\Component\Uid\UuidV7;

final readonly class LinkAccountMessage extends SyncUserMessage
{
    public function __construct(
        UuidV7 $userId,
        public string $code,
    ) {
        parent::__construct($userId);
    }
}
