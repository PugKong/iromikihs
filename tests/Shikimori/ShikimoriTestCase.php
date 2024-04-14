<?php

declare(strict_types=1);

namespace App\Tests\Shikimori;

use App\Shikimori\Client\ListRequest;
use App\Shikimori\Client\Request;
use App\Shikimori\Client\ShikimoriHttp;
use App\Tests\Trait\GetService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;

use function is_array;

use const JSON_THROW_ON_ERROR;

abstract class ShikimoriTestCase extends KernelTestCase
{
    use GetService;

    protected static MockResponse $response;

    /**
     * @phpstan-template T of object
     *
     * @phpstan-param Request<T> $request
     *
     * @phpstan-return ($request is ListRequest ? T[] : T)
     */
    protected static function request(Request $request, MockResponse|array $response): object|array
    {
        if (is_array($response)) {
            $response = self::createJsonResponse($response);
        }
        self::$response = $response;

        return self::getShikimoriWithHttpClient(new MockHttpClient($response))->request($request);
    }

    protected static function getShikimoriWithHttpClient(HttpClientInterface $http): ShikimoriHttp
    {
        self::getContainer()->set(HttpClientInterface::class, $http);

        return self::getService(ShikimoriHttp::class);
    }

    /**
     * @param array<array-key, mixed> $data
     */
    protected static function createJsonResponse(array $data): MockResponse
    {
        $json = json_encode($data, JSON_THROW_ON_ERROR);

        return new MockResponse($json);
    }

    protected static function assertRoute(string $method, string $uri): void
    {
        self::assertSame($method, self::$response->getRequestMethod());
        self::assertSame("https://shikimori.example.com$uri", self::$response->getRequestUrl());
    }

    protected static function assertUserAgent(): void
    {
        self::assertContains('User-Agent: iromikihs_test', self::$response->getRequestOptions()['headers']);
    }

    protected static function assertFormRequest(): void
    {
        $headers = self::$response->getRequestOptions()['headers'];
        self::assertContains('Content-Type: application/x-www-form-urlencoded', $headers);
    }

    protected static function assertAuthorization(string $token): void
    {
        $headers = self::$response->getRequestOptions()['headers'];
        self::assertContains("Authorization: Bearer $token", $headers);
    }

    protected static function assertNoAuthorization(): void
    {
        $headers = self::$response->getRequestOptions()['headers'];
        foreach ($headers as $header) {
            self::assertStringStartsNotWith('Authorization:', $header, 'Authorization header should not be present');
        }
    }

    /**
     * @param array<string, string> $data
     */
    protected function assertFormData(array $data): void
    {
        parse_str(self::$response->getRequestOptions()['body'], $body);
        self::assertSame($data, $body);
    }
}
