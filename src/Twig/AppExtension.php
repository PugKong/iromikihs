<?php

declare(strict_types=1);

namespace App\Twig;

use App\Shikimori\Client\Config;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class AppExtension extends AbstractExtension
{
    private Config $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('linkAccountUrl', $this->config->authUrl(...)),
        ];
    }
}
