<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Shikimori\Api\Enum\Kind;
use App\Shikimori\Api\Enum\Status;
use App\Shikimori\Api\Enum\UserAnimeStatus;
use App\Tests\Factory\AnimeFactory;
use App\Tests\Factory\AnimeRateFactory;
use App\Tests\Factory\UserFactory;

final class AnimeControllerTest extends ControllerTestCase
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
            ['GET', '/'],
            ['POST', '/sync'],
        ];
    }

    public function testIndex(): void
    {
        $user = UserFactory::new()->withLinkedAccount()->create();

        $anime = AnimeFactory::createOne([
            'name' => $animeName = 'The anime',
            'kind' => $animeKind = Kind::MOVIE,
            'status' => $animeStatus = Status::RELEASED,
        ]);
        AnimeRateFactory::createOne([
            'user' => $user,
            'anime' => $anime,
            'status' => $rateProgress = UserAnimeStatus::WATCHING,
            'score' => $rateScore = 9,
        ]);

        self::getClient()
            ->loginUser($user->object())
            ->request('GET', '/')
        ;

        self::assertResponseIsSuccessful();
        self::assertPageTitleContains('Anime list');

        self::assertHasPageHeader('Anime list');
        self::assertHasSyncStatusComponent();

        self::assertTable(
            'table.anime-list',
            [['Name', 'Kind', 'Status', 'Progress', 'Score']],
            [[$animeName, $animeKind->value, $animeStatus->value, $rateProgress->value, (string) $rateScore]],
        );
    }

    public function testIndexQueryCount(): void
    {
        $user = UserFactory::createOne();
        AnimeRateFactory::createMany($rates = 10, ['user' => $user]);

        self::enableProfiler();
        self::getClient()
            ->loginUser($user->object())
            ->request('GET', '/')
        ;
        self::assertResponseIsSuccessful();

        self::assertTableRowsCount('table.anime-list', $rates);

        // 1 request to fetch user
        // 2 requests to build nav bar
        // 1 request to load page data
        self::assertSame(4, self::dbCollector()->getQueryCount());
    }
}
