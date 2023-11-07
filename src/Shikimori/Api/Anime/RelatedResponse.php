<?php

declare(strict_types=1);

namespace App\Shikimori\Api\Anime;

final readonly class RelatedResponse
{
    public function __construct(
        public string $relation,
        public ?RelatedResponseAnimeItem $anime,
    ) {
    }
}
