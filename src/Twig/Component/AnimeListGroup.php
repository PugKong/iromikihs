<?php

declare(strict_types=1);

namespace App\Twig\Component;

use App\Repository\UserRatedAnime;
use App\Shikimori\Client\Config;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class AnimeListGroup
{
    public string $title;
    /**
     * @var UserRatedAnime[]
     */
    public array $items = [];

    private Config $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function getUrl(UserRatedAnime $anime): string
    {
        return $this->config->baseUrl.$anime->animeUrl;
    }
}
