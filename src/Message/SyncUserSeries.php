<?php

declare(strict_types=1);

namespace App\Message;

use Symfony\Component\Uid\UuidV7;

final readonly class SyncUserSeries
{
    public function __construct(public UuidV7 $userId)
    {
    }
}
