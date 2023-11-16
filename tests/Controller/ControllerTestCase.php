<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Tests\Trait\GetService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\DataCollector\DoctrineDataCollector;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpKernel\Profiler\Profile;
use Symfony\Component\HttpKernel\Profiler\Profiler;
use Zenstruck\Foundry\Test\Factories;

abstract class ControllerTestCase extends WebTestCase
{
    use Factories;
    use GetService;

    private static ?KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();

        self::$client = self::createClient();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        self::$client = null;
    }

    public static function getClient(): KernelBrowser
    {
        self::assertNotNull(self::$client);

        return self::$client;
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
                ->filterXPath('//td')->each(
                    fn (Crawler $cell) => $cell->text(),
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

    public static function enableProfiler(): void
    {
        self::getClient()->enableProfiler();
        self::getService(EntityManagerInterface::class)->clear();
        $profiler = self::getClient()->getContainer()->get('profiler');
        self::assertInstanceOf(Profiler::class, $profiler);
        $profiler->reset();
    }

    public static function dbCollector(): DoctrineDataCollector
    {
        $profile = self::getClient()->getProfile();
        self::assertInstanceOf(Profile::class, $profile);
        $dbCollector = $profile->getCollector('db');
        self::assertInstanceOf(DoctrineDataCollector::class, $dbCollector);

        return $dbCollector;
    }
}
