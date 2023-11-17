<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\AnimeRateStatus;
use App\Entity\SeriesState;
use App\Entity\UserSyncState;
use App\Shikimori\Api\Enum\Kind;
use App\Shikimori\Api\Enum\Status;
use App\Tests\Factory\AnimeFactory;
use App\Tests\Factory\AnimeRateFactory;
use App\Tests\Factory\SeriesFactory;
use App\Tests\Factory\SeriesRateFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\Twig\Component\CsrfTokenManagerSpy;
use App\Twig\Component\SimpleForm;

use function is_callable;

final class AnimeControllerTest extends ControllerTestCase
{
    /**
     * @dataProvider requiresAuthenticationProvider
     */
    public function testRequiresAuthentication(string $method, string|callable $uri): void
    {
        if (is_callable($uri)) {
            $uri = $uri();
        }
        self::assertRequiresAuthentication($method, $uri);
    }

    public static function requiresAuthenticationProvider(): array
    {
        return [
            ['GET', '/'],
            ['POST', fn () => sprintf('/animes/%d/skip', AnimeFactory::createOne()->getId())],
            ['POST', fn () => sprintf('/animes/%d/observe', AnimeFactory::createOne()->getId())],
        ];
    }

    public function testIndex(): void
    {
        $user = UserFactory::new()->withLinkedAccount()->create();

        $rateFactory = AnimeRateFactory::new(['user' => $user]);
        $rateFactory->create([
            'anime' => AnimeFactory::new([
                'name' => 'Watching',
                'kind' => Kind::MOVIE,
                'status' => Status::RELEASED,
            ]),
            'status' => AnimeRateStatus::WATCHING,
            'score' => 9,
        ]);
        $rateFactory->create([
            'anime' => AnimeFactory::new([
                'name' => 'Rewatching',
                'kind' => Kind::MOVIE,
                'status' => Status::RELEASED,
            ]),
            'status' => AnimeRateStatus::REWATCHING,
            'score' => 8,
        ]);
        $rateFactory->create([
            'anime' => AnimeFactory::new([
                'name' => 'On hold',
                'kind' => Kind::TV,
                'status' => Status::ONGOING,
            ]),
            'status' => AnimeRateStatus::ON_HOLD,
            'score' => 7,
        ]);
        $rateFactory->create([
            'anime' => AnimeFactory::new([
                'name' => 'Planned',
                'kind' => Kind::OVA,
                'status' => Status::ANONS,
            ]),
            'status' => AnimeRateStatus::PLANNED,
            'score' => 6,
        ]);
        $rateFactory->create([
            'anime' => AnimeFactory::new([
                'name' => 'Completed',
                'kind' => Kind::ONA,
                'status' => Status::RELEASED,
            ]),
            'status' => AnimeRateStatus::COMPLETED,
            'score' => 5,
        ]);
        $rateFactory->create([
            'anime' => AnimeFactory::new([
                'name' => 'Skipped',
                'kind' => Kind::TV,
                'status' => Status::RELEASED,
            ]),
            'status' => AnimeRateStatus::SKIPPED,
            'score' => 0,
        ]);
        $rateFactory->create([
            'anime' => AnimeFactory::new([
                'name' => 'Dropped',
                'kind' => Kind::TV,
                'status' => Status::RELEASED,
            ]),
            'status' => AnimeRateStatus::DROPPED,
            'score' => 3,
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
            [['Name', 'Kind', 'Status', 'Score']],
            [
                ['Watching / Rewatching 2'],
                ['Rewatching', 'movie', 'released', '8'],
                ['Watching', 'movie', 'released', '9'],
                ['On hold 1'],
                ['On hold', 'tv', 'ongoing', '7'],
                ['Planned 1'],
                ['Planned', 'ova', 'anons', '6'],
                ['Completed 1'],
                ['Completed', 'ona', 'released', '5'],
                ['Dropped / Skipped 2'],
                ['Dropped', 'tv', 'released', '3'],
                ['Skipped', 'tv', 'released', '—'],
            ],
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

        $staticRowsCount = 5;
        self::assertTableRowsCount('table.anime-list', $staticRowsCount + $rates);

        // 1 request to fetch user
        // 4 requests to build nav bar
        // 1 request to load page data
        self::assertSame(6, self::dbCollector()->getQueryCount());
    }

    /**
     * @dataProvider skipProvider
     */
    public function testSkip(string $fromUrl, SeriesState $initialSeriesState, SeriesState $finalSeriesState): void
    {
        $user = UserFactory::createOne();
        $series = SeriesFactory::createOne();
        $seriesRate = SeriesRateFactory::createOne([
            'user' => $user,
            'series' => $series,
            'state' => $initialSeriesState,
        ]);
        $anime1 = AnimeFactory::createOne(['series' => $series, 'status' => Status::RELEASED]);
        $anime2 = AnimeFactory::createOne(['series' => $series, 'status' => Status::RELEASED]);
        AnimeRateFactory::createOne(['user' => $user, 'anime' => $anime1, 'status' => AnimeRateStatus::COMPLETED]);

        self::getClient()
            ->loginUser($user->object())
            ->request('GET', $fromUrl)
        ;
        self::assertResponseIsSuccessful();

        self::getClient()->submitForm('Skip');
        self::assertResponseRedirects('http://localhost'.$fromUrl);

        $rate = AnimeRateFactory::find(['anime' => $anime2]);
        self::assertSame(AnimeRateStatus::SKIPPED, $rate->getStatus());
        self::assertSame($finalSeriesState, $seriesRate->getState());
    }

    public static function skipProvider(): array
    {
        return [
            'incomplete list' => ['/series/incomplete', SeriesState::INCOMPLETE, SeriesState::COMPLETE],
            'complete list' => ['/series/complete', SeriesState::COMPLETE, SeriesState::COMPLETE],
            'dropped list' => ['/series/dropped', SeriesState::DROPPED, SeriesState::DROPPED],
        ];
    }

    public function testSkipChecksCsrfToken(): void
    {
        $user = UserFactory::createOne();
        $anime = AnimeFactory::createOne();

        self::getClient()
            ->loginUser($user->object())
            ->request('POST', sprintf('/animes/%d/skip', $anime->getId()))
        ;
        self::assertResponseIsUnprocessable();

        self::getClient()->request(
            'POST',
            sprintf('/animes/%d/skip', $anime->getId()),
            [SimpleForm::CSRF_TOKEN_FIELD => '123'],
        );
        self::assertResponseIsUnprocessable();
    }

    /**
     * @dataProvider checksSyncStatusProvider
     */
    public function testSkipChecksSyncStatus(?UserSyncState $state, bool $allowed): void
    {
        $user = UserFactory::new()->withSyncStatus(state: $state)->create();
        $series = SeriesFactory::createOne();
        SeriesRateFactory::createOne([
            'user' => $user,
            'series' => $series,
            'state' => SeriesState::DROPPED,
        ]);
        $anime = AnimeFactory::createOne(['series' => $series, 'status' => Status::RELEASED]);

        $csrfTokenManager = new CsrfTokenManagerSpy([SimpleForm::CSRF_TOKEN_ID => $csrfToken = '123']);
        $csrfTokenManager->register(self::getContainer());

        self::getClient()
            ->loginUser($user->object())
            ->request(
                'POST',
                sprintf('/animes/%s/skip', $anime->getId()),
                [SimpleForm::CSRF_TOKEN_FIELD => $csrfToken],
            )
        ;
        self::assertResponseRedirects('/');
        self::getClient()->followRedirect();

        $error = 'Can not skip anime while syncing data';
        if ($allowed) {
            self::assertHasNoFlashError($error);
        } else {
            self::assertHasFlashError($error);
        }
    }

    public static function checksSyncStatusProvider(): array
    {
        return [
            'no sync' => [null, true],
            'linking account' => [UserSyncState::LINK_ACCOUNT, false],
            'syncing rates' => [UserSyncState::ANIME_RATES, false],
            'syncing series' => [UserSyncState::SERIES, false],
            'building series rates' => [UserSyncState::SERIES_RATES, false],
            'last sync failed' => [UserSyncState::FAILED, true],
        ];
    }

    public function testSkipRatedAnime(): void
    {
        $user = UserFactory::createOne();
        $series = SeriesFactory::createOne();
        SeriesRateFactory::createOne([
            'user' => $user,
            'series' => $series,
            'state' => SeriesState::DROPPED,
        ]);
        $anime = AnimeFactory::createOne(['series' => $series, 'status' => Status::RELEASED]);
        AnimeRateFactory::createOne(['user' => $user, 'anime' => $anime]);

        $csrfTokenManager = new CsrfTokenManagerSpy([SimpleForm::CSRF_TOKEN_ID => $csrfToken = '123']);
        $csrfTokenManager->register(self::getContainer());

        self::getClient()
            ->loginUser($user->object())
            ->request(
                'POST',
                sprintf('/animes/%s/skip', $anime->getId()),
                [SimpleForm::CSRF_TOKEN_FIELD => $csrfToken],
            )
        ;
        self::assertResponseRedirects('/');
        self::getClient()->followRedirect();

        self::assertHasFlashError('Can not skip rated anime');
    }

    public function testSkipNotRatedSeries(): void
    {
        $user = UserFactory::createOne();
        $series = SeriesFactory::createOne();
        $anime = AnimeFactory::createOne(['series' => $series, 'status' => Status::RELEASED]);

        $csrfTokenManager = new CsrfTokenManagerSpy([SimpleForm::CSRF_TOKEN_ID => $csrfToken = '123']);
        $csrfTokenManager->register(self::getContainer());

        self::getClient()
            ->loginUser($user->object())
            ->request(
                'POST',
                sprintf('/animes/%s/skip', $anime->getId()),
                [SimpleForm::CSRF_TOKEN_FIELD => $csrfToken],
            )
        ;
        self::assertResponseRedirects('/');
        self::getClient()->followRedirect();

        self::assertHasFlashError('Can not skip anime in not rated series');
    }

    public function testSkipNoSeries(): void
    {
        $user = UserFactory::createOne();
        $anime = AnimeFactory::createOne(['status' => Status::RELEASED]);

        $csrfTokenManager = new CsrfTokenManagerSpy([SimpleForm::CSRF_TOKEN_ID => $csrfToken = '123']);
        $csrfTokenManager->register(self::getContainer());

        self::getClient()
            ->loginUser($user->object())
            ->request(
                'POST',
                sprintf('/animes/%s/skip', $anime->getId()),
                [SimpleForm::CSRF_TOKEN_FIELD => $csrfToken],
            )
        ;
        self::assertResponseRedirects('/');
        self::getClient()->followRedirect();

        self::assertHasFlashError('Oh no, anime has no series');
    }

    /**
     * @dataProvider observeProvider
     */
    public function testObserve(string $fromUrl, SeriesState $initialSeriesState, SeriesState $finalSeriesState): void
    {
        $user = UserFactory::createOne();
        $series = SeriesFactory::createOne();
        $seriesRate = SeriesRateFactory::createOne([
            'user' => $user,
            'series' => $series,
            'state' => $initialSeriesState,
        ]);
        $anime1 = AnimeFactory::createOne(['series' => $series, 'status' => Status::RELEASED]);
        $anime2 = AnimeFactory::createOne(['series' => $series, 'status' => Status::RELEASED]);
        AnimeRateFactory::createOne(['user' => $user, 'anime' => $anime1, 'status' => AnimeRateStatus::COMPLETED]);
        $rate = AnimeRateFactory::new()->skipped()->create(['user' => $user, 'anime' => $anime2]);

        self::getClient()
            ->loginUser($user->object())
            ->request('GET', $fromUrl)
        ;
        self::assertResponseIsSuccessful();

        self::getClient()->submitForm('Observe');
        self::assertResponseRedirects('http://localhost'.$fromUrl);

        $rate->assertNotPersisted();
        self::assertSame($finalSeriesState, $seriesRate->getState());
    }

    public static function observeProvider(): array
    {
        return [
            'incomplete list' => ['/series/incomplete', SeriesState::INCOMPLETE, SeriesState::INCOMPLETE],
            'complete list' => ['/series/complete', SeriesState::COMPLETE, SeriesState::INCOMPLETE],
            'dropped list' => ['/series/dropped', SeriesState::DROPPED, SeriesState::DROPPED],
        ];
    }

    public function testObserveChecksCsrfToken(): void
    {
        $user = UserFactory::createOne();
        $anime = AnimeFactory::createOne();

        self::getClient()
            ->loginUser($user->object())
            ->request('POST', sprintf('/animes/%d/observe', $anime->getId()))
        ;
        self::assertResponseIsUnprocessable();

        self::getClient()->request(
            'POST',
            sprintf('/animes/%d/observe', $anime->getId()),
            [SimpleForm::CSRF_TOKEN_FIELD => '123'],
        );
        self::assertResponseIsUnprocessable();
    }

    /**
     * @dataProvider checksSyncStatusProvider
     */
    public function testObserveChecksSyncStatus(?UserSyncState $state, bool $allowed): void
    {
        $user = UserFactory::new()->withSyncStatus(state: $state)->create();
        $series = SeriesFactory::createOne();
        SeriesRateFactory::createOne([
            'user' => $user,
            'series' => $series,
            'state' => SeriesState::DROPPED,
        ]);
        $anime = AnimeFactory::createOne(['series' => $series, 'status' => Status::RELEASED]);
        AnimeRateFactory::new()->skipped()->create(['user' => $user, 'anime' => $anime]);

        $csrfTokenManager = new CsrfTokenManagerSpy([SimpleForm::CSRF_TOKEN_ID => $csrfToken = '123']);
        $csrfTokenManager->register(self::getContainer());

        self::getClient()
            ->loginUser($user->object())
            ->request(
                'POST',
                sprintf('/animes/%s/observe', $anime->getId()),
                [SimpleForm::CSRF_TOKEN_FIELD => $csrfToken],
            )
        ;
        self::assertResponseRedirects('/');
        self::getClient()->followRedirect();

        $error = 'Can not observe anime while syncing data';
        if ($allowed) {
            self::assertHasNoFlashError($error);
        } else {
            self::assertHasFlashError($error);
        }
    }

    public function testObserveHasNoAnimeRateInSkippedStatus(): void
    {
        $user = UserFactory::createOne();
        $anime = AnimeFactory::createOne(['status' => Status::RELEASED]);

        $csrfTokenManager = new CsrfTokenManagerSpy([SimpleForm::CSRF_TOKEN_ID => $csrfToken = '123']);
        $csrfTokenManager->register(self::getContainer());

        self::getClient()
            ->loginUser($user->object())
            ->request(
                'POST',
                sprintf('/animes/%s/observe', $anime->getId()),
                [SimpleForm::CSRF_TOKEN_FIELD => $csrfToken],
            )
        ;
        self::assertResponseRedirects('/');
        self::getClient()->followRedirect();

        self::assertHasFlashError('Anime was not skipped');
    }

    public function testObserveNotRatedSeries(): void
    {
        $user = UserFactory::createOne();
        $series = SeriesFactory::createOne();
        $anime = AnimeFactory::createOne(['series' => $series, 'status' => Status::RELEASED]);
        AnimeRateFactory::new()->skipped()->create(['user' => $user, 'anime' => $anime]);

        $csrfTokenManager = new CsrfTokenManagerSpy([SimpleForm::CSRF_TOKEN_ID => $csrfToken = '123']);
        $csrfTokenManager->register(self::getContainer());

        self::getClient()
            ->loginUser($user->object())
            ->request(
                'POST',
                sprintf('/animes/%s/observe', $anime->getId()),
                [SimpleForm::CSRF_TOKEN_FIELD => $csrfToken],
            )
        ;
        self::assertResponseRedirects('/');
        self::getClient()->followRedirect();

        self::assertHasFlashError('Can not observe anime in not rated series');
    }

    public function testObserveNotSeries(): void
    {
        $user = UserFactory::createOne();
        $anime = AnimeFactory::createOne(['status' => Status::RELEASED]);
        AnimeRateFactory::new()->skipped()->create(['user' => $user, 'anime' => $anime]);

        $csrfTokenManager = new CsrfTokenManagerSpy([SimpleForm::CSRF_TOKEN_ID => $csrfToken = '123']);
        $csrfTokenManager->register(self::getContainer());

        self::getClient()
            ->loginUser($user->object())
            ->request(
                'POST',
                sprintf('/animes/%s/observe', $anime->getId()),
                [SimpleForm::CSRF_TOKEN_FIELD => $csrfToken],
            )
        ;
        self::assertResponseRedirects('/');
        self::getClient()->followRedirect();

        self::assertHasFlashError('Oh no, anime has no series');
    }
}
