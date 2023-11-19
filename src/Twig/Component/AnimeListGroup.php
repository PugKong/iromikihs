<?php

declare(strict_types=1);

namespace App\Twig\Component;

use App\Repository\UserRatedAnime;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class AnimeListGroup
{
    public string $title;
    /**
     * @var UserRatedAnime[]
     */
    public array $items = [];
}
