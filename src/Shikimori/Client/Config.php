<?php

declare(strict_types=1);

namespace App\Shikimori\Client;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

readonly class Config
{
    public function __construct(
        private RouterInterface $router,
        #[Autowire(env: 'SHIKIMORI_APP')]
        public string $app,
        #[Autowire(env: 'SHIKIMORI_BASE_URL')]
        public string $baseUrl,
        #[Autowire(env: 'SHIKIMORI_CLIENT_ID')]
        public string $clientId,
        #[Autowire(env: 'SHIKIMORI_CLIENT_SECRET')]
        public string $clientSecret,
    ) {
    }

    public function redirectUrl(): string
    {
        return $this->router->generate('app_profile_link', referenceType: UrlGeneratorInterface::ABSOLUTE_URL);
    }

    public function authUrl(): string
    {
        $url = $this->baseUrl.'/oauth/authorize';
        $query = http_build_query([
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUrl(),
            'response_type' => 'code',
        ]);

        return "$url?$query";
    }
}
