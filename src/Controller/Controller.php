<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

use function strlen;

abstract class Controller extends AbstractController
{
    public const COMMON_CSRF_TOKEN_ID = 'common';
    public const COMMON_CSRF_TOKEN_FIELD = '_token';

    /**
     * @phpstan-template T of string|null
     *
     * @phpstan-param T $default
     *
     * @phpstan-return string|T
     */
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

    /**
     * @throws UnprocessableEntityHttpException
     */
    protected function checkSimpleCsrfToken(): void
    {
        $request = $this->container->get('request_stack')->getCurrentRequest();
        if (null === $request) {
            return;
        }

        $csrfToken = (string) $request->request->get(self::COMMON_CSRF_TOKEN_FIELD);
        if (!$this->isCsrfTokenValid(self::COMMON_CSRF_TOKEN_ID, $csrfToken)) {
            throw new UnprocessableEntityHttpException('Invalid csrf token');
        }
    }

    protected function addFlashError(string $message): void
    {
        $this->addFlash('error', $message);
    }
}
