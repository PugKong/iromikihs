<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\SeriesState;
use App\Tests\Factory\AnimeFactory;
use App\Tests\Factory\AnimeRateFactory;
use App\Tests\Factory\SeriesFactory;
use App\Tests\Factory\SeriesRateFactory;
use App\Tests\Factory\UserFactory;

final class SeriesControllerTest extends ControllerTestCase
{
    /**
     * @dataProvider requiresAuthenticationProvider
     */
    public function testRequiresAuthentication(string $method, string $uri): void
    {
        self::assertRequiresAuthentication($method, $uri);
    }

    public static function requiresAuthenticationProvider(): array
    {
        return [
            ['GET', '/series/incomplete'],
            ['GET', '/series/complete'],
        ];
    }

    public function testIncomplete(): void
    {
        $user = UserFactory::createOne();
        $series = SeriesFactory::createOne();
        $anime1 = AnimeFactory::createOne(['series' => $series]);
        $anime2 = AnimeFactory::createOne(['series' => $series]);
        AnimeRateFactory::createOne(['user' => $user, 'anime' => $anime1]);
        AnimeRateFactory::createOne(['user' => $user, 'anime' => $anime2]);
        SeriesRateFactory::createOne(['user' => $user, 'series' => $series, 'state' => SeriesState::INCOMPLETE]);

        self::getClient()
            ->loginUser($user->object())
            ->request('GET', '/series/incomplete')
        ;
        self::assertResponseIsSuccessful();
        self::assertPageTitleContains('Incomplete series');

        self::assertHasPageHeader('Incomplete series');
        self::assertHasSyncStatusComponent();

        $sections = self::getClient()->getCrawler()->filter('section.series-list');
        self::assertCount(1, $sections);

        $listItems = $sections->first()->filter('div.series-list-item');
        self::assertCount(2, $listItems);
    }

    public function testComplete(): void
    {
        $user = UserFactory::createOne();
        $series = SeriesFactory::createOne();
        $anime1 = AnimeFactory::createOne(['series' => $series]);
        $anime2 = AnimeFactory::createOne(['series' => $series]);
        AnimeRateFactory::createOne(['user' => $user, 'anime' => $anime1]);
        AnimeRateFactory::createOne(['user' => $user, 'anime' => $anime2]);
        SeriesRateFactory::createOne(['user' => $user, 'series' => $series, 'state' => SeriesState::COMPLETE]);

        self::getClient()
            ->loginUser($user->object())
            ->request('GET', '/series/complete')
        ;
        self::assertResponseIsSuccessful();
        self::assertPageTitleContains('Completed series');

        self::assertHasPageHeader('Completed series');
        self::assertHasSyncStatusComponent();

        $sections = self::getClient()->getCrawler()->filter('section.series-list');
        self::assertCount(1, $sections);

        $listItems = $sections->first()->filter('div.series-list-item');
        self::assertCount(2, $listItems);
    }
}
