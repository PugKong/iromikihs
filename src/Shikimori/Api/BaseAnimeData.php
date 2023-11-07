<?php

declare(strict_types=1);

namespace App\Shikimori\Api;

use App\Shikimori\Api\Enum\Kind;
use App\Shikimori\Api\Enum\Status;

readonly class BaseAnimeData
{
    public function __construct(
        public int $id,
        public string $name,
        public string $url,
        public ?Kind $kind,
        public Status $status,
    ) {
    }
}
