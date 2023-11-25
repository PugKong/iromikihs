<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Controller\Controller;
use App\Tests\TestDouble\CsrfTokenManagerSpy;
use App\Tests\Trait\GetService;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\UX\Turbo\TurboBundle;
use Zenstruck\Foundry\Test\Factories;

abstract class ControllerTestCase extends WebTestCase
{
    use Factories;
    use GetService;

    private static ?KernelBrowser $client = null;

    protected function tearDown(): void
    {
        parent::tearDown();

        self::$client = null;
    }

    public static function getClient(): KernelBrowser
    {
        if (null === self::$client) {
            self::ensureKernelShutdown();
            self::$client = self::createClient();
        }

        return self::$client;
    }

    public static function requestWithCsrfToken(
        string $method,
        string $uri,
        array $parameters = [],
        array $server = [],
    ): Crawler {
        $client = self::getClient();
        $csrfTokenManager = new CsrfTokenManagerSpy([Controller::COMMON_CSRF_TOKEN_ID => $csrfToken = '123']);
        $csrfTokenManager->register($client->getContainer());

        return $client->request(
            method: $method,
            uri: $uri,
            parameters: [...$parameters, Controller::COMMON_CSRF_TOKEN_FIELD => $csrfToken],
            server: $server,
        );
    }

    public static function requestTurboWithCsrfToken(
        string $method,
        string $uri,
        array $parameters = [],
        array $server = [],
    ): Crawler {
        return self::requestWithCsrfToken(
            method: $method,
            uri: $uri,
            parameters: $parameters,
            server: [...$server, 'HTTP_ACCEPT' => TurboBundle::STREAM_MEDIA_TYPE],
        );
    }

    public static function assertResponseIsTurbo(): void
    {
        self::assertStringStartsWith(
            TurboBundle::STREAM_MEDIA_TYPE,
            self::getClient()->getResponse()->headers->get('content-type', ''),
        );
    }

    public static function assertRequiresAuthentication(string $method, string $uri): void
    {
        self::getClient()->request($method, $uri);
        self::assertResponseRedirects('http://localhost/login');
    }

    public static function assertHasFlashError(string $message): void
    {
        $crawler = self::getClient()->getCrawler();
        $errors = $crawler->filter('section.bg-error')->each(fn (Crawler $c) => $c->text());
        self::assertContains($message, $errors, sprintf('Got errors: %s', var_export($errors, true)));
    }

    public static function assertHasNoFlashError(string $message): void
    {
        $crawler = self::getClient()->getCrawler();
        $errors = $crawler->filter('section.bg-error')->each(fn (Crawler $c) => $c->text());
        self::assertNotContains($message, $errors, sprintf('Got errors: %s', var_export($errors, true)));
    }

    public static function assertTable(string $selector, array $expectedHeaders, array $expectedBody): void
    {
        $crawler = self::getClient()->getCrawler();

        $table = $crawler->filter($selector);
        self::assertCount(1, $table);

        $actualHeaders = $table->filterXPath('//thead/tr')->each(
            fn (Crawler $row): array => $row
                ->filterXPath('//th')->each(
                    fn (Crawler $cell) => $cell->text(),
                ),
        );
        self::assertSame($expectedHeaders, $actualHeaders);

        $actualBody = $table->filterXPath('//tbody/tr')->each(
            fn (Crawler $row): array => $row
                ->filterXPath('//td|//th')->each(
                    function (Crawler $cell) {
                        if (0 === $cell->children()->count()) {
                            return $cell->text();
                        }

                        $texts = $cell
                            ->children()
                            ->filterXPath('*[not(contains(@class, "md:hidden"))]')
                            ->each(fn (Crawler $c) => $c->text())
                        ;

                        return implode(' ', $texts);
                    },
                ),
        );
        self::assertSame($expectedBody, $actualBody);
    }

    public static function assertTableRowsCount(string $selector, int $expected): void
    {
        $crawler = self::getClient()->getCrawler();

        $table = $crawler->filter($selector);
        self::assertCount(1, $table);

        $actual = $table->filterXPath('//tbody/tr')->count();
        self::assertSame($expected, $actual);
    }

    public static function assertHasPageHeader(string $header): void
    {
        $crawler = self::getClient()->getCrawler();
        self::assertSame($header, $crawler->filter('section.page-header')->text());
    }

    public static function assertHasSyncStatusComponent(): void
    {
        $crawler = self::getClient()->getCrawler();
        self::assertCount(1, $crawler->filter('section.sync-status'));
    }
}
