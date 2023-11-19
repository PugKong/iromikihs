<?php

declare(strict_types=1);

namespace App\Twig;

use App\Shikimori\Client\Config;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class AppExtension extends AbstractExtension
{
    private Config $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('shikimori_url', $this->shikimoriUrl(...)),
        ];
    }

    public function shikimoriUrl(string $url): string
    {
        return $this->config->baseUrl.$url;
    }
}
