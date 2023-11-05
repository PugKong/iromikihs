<?php

declare(strict_types=1);

namespace App\Twig\Component;

use App\Shikimori\Client\Config;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final readonly class LinkAccountSection
{
    private Config $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function getLinkUrl(): string
    {
        return $this->config->authUrl();
    }
}
