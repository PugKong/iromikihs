<?php

declare(strict_types=1);

namespace App\Message;

use Symfony\Component\Uid\UuidV7;

abstract readonly class SyncUserMessage
{
    public function __construct(public UuidV7 $userId)
    {
    }
}
