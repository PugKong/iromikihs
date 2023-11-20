<?php

declare(strict_types=1);

namespace App\Exception;

use App\Entity\Anime;
use RuntimeException;

final class AnimeHasNoSeriesException extends RuntimeException
{
    public static function create(Anime $anime): self
    {
        return new self(sprintf('Anime %d has no series', $anime->getId()));
    }
}
