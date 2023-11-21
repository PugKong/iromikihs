<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use function strlen;

abstract class Controller extends AbstractController
{
    protected function getRefererPath(string $default = null): ?string
    {
        $request = $this->container->get('request_stack')->getCurrentRequest();
        if (null === $request) {
            return $default;
        }

        $referer = $request->headers->get('referer');
        if (null === $referer) {
            return $default;
        }

        $host = $request->getSchemeAndHttpHost();
        if (!str_starts_with($referer, $host)) {
            return $default;
        }

        return substr($referer, strlen($host));
    }
}
