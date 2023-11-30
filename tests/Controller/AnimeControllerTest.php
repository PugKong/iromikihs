<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Controller\Controller;
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

        $client = self::createClient();
        $client->loginUser($user->object());
        $client->request('GET', '/');

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

        $client = self::createClient();
        $client->loginUser($user->object());
        $client->request('GET', $fromUrl);
        self::assertResponseIsSuccessful();

        $client->submitForm('Skip');
        self::assertResponseRedirects($fromUrl);

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

    public function testSkipTurbo(): void
    {
        $user = UserFactory::createOne();
        $series = SeriesFactory::createOne();
        $seriesRate = SeriesRateFactory::createOne([
            'user' => $user,
            'series' => $series,
            'state' => SeriesState::INCOMPLETE,
        ]);
        $anime1 = AnimeFactory::createOne(['series' => $series, 'status' => Status::RELEASED]);
        $anime2 = AnimeFactory::createOne(['series' => $series, 'status' => Status::RELEASED]);
        AnimeFactory::createOne(['series' => $series, 'status' => Status::RELEASED]);
        AnimeRateFactory::createOne(['user' => $user, 'anime' => $anime1, 'status' => AnimeRateStatus::COMPLETED]);

        $client = self::createClient();
        $client->loginUser($user->object());
        self::requestTurboWithCsrfToken('POST', sprintf('/animes/%d/skip', $anime2->getId()));
        self::assertResponseIsSuccessful();
        self::assertResponseIsTurbo();

        $crawler = $client->getCrawler();
        self::assertCount(1, $streams = $crawler->filter('turbo-stream[action]'));
        self::assertCount(1, $button = $streams->filter('[action="replace"]'));
        self::assertSame("anime-{$anime2->getId()}-skip-form", $button->attr('target'));

        $rate = AnimeRateFactory::find(['anime' => $anime2]);
        self::assertSame(AnimeRateStatus::SKIPPED, $rate->getStatus());
        self::assertSame(SeriesState::INCOMPLETE, $seriesRate->getState());
    }

    public function testSkipTurboChangeSeriesState(): void
    {
        $user = UserFactory::createOne();
        $series = SeriesFactory::createOne();
        $seriesRate = SeriesRateFactory::createOne([
            'user' => $user,
            'series' => $series,
            'state' => SeriesState::INCOMPLETE,
        ]);
        $anime1 = AnimeFactory::createOne(['series' => $series, 'status' => Status::RELEASED]);
        $anime2 = AnimeFactory::createOne(['series' => $series, 'status' => Status::RELEASED]);
        AnimeRateFactory::createOne(['user' => $user, 'anime' => $anime1, 'status' => AnimeRateStatus::COMPLETED]);

        $client = self::createClient();
        $client->loginUser($user->object());
        self::requestTurboWithCsrfToken('POST', sprintf('/animes/%d/skip', $anime2->getId()));
        self::assertResponseIsSuccessful();
        self::assertResponseIsTurbo();

        $crawler = $client->getCrawler();
        self::assertCount(2, $streams = $crawler->filter('turbo-stream[action]'));
        self::assertCount(1, $navbar = $streams->filter('[action="replace"]'));
        self::assertSame('navbar', $navbar->attr('target'));
        self::assertCount(1, $seriesStream = $crawler->filter('[action="remove"]'));
        self::assertSame('series-'.$series->getId(), $seriesStream->attr('target'));

        $rate = AnimeRateFactory::find(['anime' => $anime2]);
        self::assertSame(AnimeRateStatus::SKIPPED, $rate->getStatus());
        self::assertSame(SeriesState::COMPLETE, $seriesRate->getState());
    }

    public function testSkipChecksCsrfToken(): void
    {
        $user = UserFactory::createOne();
        $anime = AnimeFactory::createOne();

        $client = self::createClient();
        $client->loginUser($user->object());
        $client->request('POST', sprintf('/animes/%d/skip', $anime->getId()));
        self::assertResponseIsUnprocessable();

        $client->request(
            'POST',
            sprintf('/animes/%d/skip', $anime->getId()),
            [Controller::COMMON_CSRF_TOKEN_FIELD => '123'],
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

        $client = self::createClient();
        $client->loginUser($user->object());
        self::requestWithCsrfToken('POST', sprintf('/animes/%s/skip', $anime->getId()));
        self::assertResponseRedirects('/');
        $client->followRedirect();

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

        $client = self::createClient();
        $client->loginUser($user->object());
        self::requestWithCsrfToken('POST', sprintf('/animes/%s/skip', $anime->getId()));
        self::assertResponseRedirects('/');
        $client->followRedirect();

        self::assertHasFlashError('Can not skip rated anime');
    }

    public function testSkipNotRatedSeries(): void
    {
        $user = UserFactory::createOne();
        $series = SeriesFactory::createOne();
        $anime = AnimeFactory::createOne(['series' => $series, 'status' => Status::RELEASED]);

        $client = self::createClient();
        $client->loginUser($user->object());
        self::requestWithCsrfToken('POST', sprintf('/animes/%s/skip', $anime->getId()));
        self::assertResponseRedirects('/');
        $client->followRedirect();

        self::assertHasFlashError('Can not skip anime in not rated series');
    }

    public function testSkipNoSeries(): void
    {
        $user = UserFactory::createOne();
        $anime = AnimeFactory::createOne(['status' => Status::RELEASED]);

        $client = self::createClient();
        $client->loginUser($user->object());
        self::requestWithCsrfToken('POST', sprintf('/animes/%s/skip', $anime->getId()));
        self::assertResponseRedirects('/');
        $client->followRedirect();

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

        $client = self::createClient();
        $client->loginUser($user->object());
        $client->request('GET', $fromUrl);

        self::assertResponseIsSuccessful();

        $client->submitForm('Observe');
        self::assertResponseRedirects($fromUrl);

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

    public function testObserveTurbo(): void
    {
        $user = UserFactory::createOne();
        $series = SeriesFactory::createOne();
        $seriesRate = SeriesRateFactory::createOne([
            'user' => $user,
            'series' => $series,
            'state' => SeriesState::INCOMPLETE,
        ]);
        $anime1 = AnimeFactory::createOne(['series' => $series, 'status' => Status::RELEASED]);
        $anime2 = AnimeFactory::createOne(['series' => $series, 'status' => Status::RELEASED]);
        AnimeFactory::createOne(['series' => $series, 'status' => Status::RELEASED]);
        AnimeRateFactory::createOne(['user' => $user, 'anime' => $anime1, 'status' => AnimeRateStatus::COMPLETED]);
        $anime2Rate = AnimeRateFactory::createOne([
            'user' => $user,
            'anime' => $anime2,
            'status' => AnimeRateStatus::SKIPPED,
        ]);

        $client = self::createClient();
        $client->loginUser($user->object());
        self::requestTurboWithCsrfToken('POST', sprintf('/animes/%d/observe', $anime2->getId()));
        self::assertResponseIsSuccessful();
        self::assertResponseIsTurbo();

        $crawler = $client->getCrawler();
        self::assertCount(1, $streams = $crawler->filter('turbo-stream[action]'));
        self::assertCount(1, $button = $streams->filter('[action="replace"]'));
        self::assertSame("anime-{$anime2->getId()}-skip-form", $button->attr('target'));

        $anime2Rate->assertNotPersisted();
        self::assertSame(SeriesState::INCOMPLETE, $seriesRate->getState());
    }

    public function testObserveTurboChangeSeriesState(): void
    {
        $user = UserFactory::createOne();
        $series = SeriesFactory::createOne();
        $seriesRate = SeriesRateFactory::createOne([
            'user' => $user,
            'series' => $series,
            'state' => SeriesState::COMPLETE,
        ]);
        $anime1 = AnimeFactory::createOne(['series' => $series, 'status' => Status::RELEASED]);
        $anime2 = AnimeFactory::createOne(['series' => $series, 'status' => Status::RELEASED]);
        AnimeRateFactory::createOne(['user' => $user, 'anime' => $anime1, 'status' => AnimeRateStatus::COMPLETED]);
        $anime2rate = AnimeRateFactory::createOne([
            'user' => $user,
            'anime' => $anime2,
            'status' => AnimeRateStatus::SKIPPED,
        ]);

        $client = self::createClient();
        $client->loginUser($user->object());
        self::requestTurboWithCsrfToken('POST', sprintf('/animes/%d/observe', $anime2->getId()));
        self::assertResponseIsSuccessful();
        self::assertResponseIsTurbo();

        $crawler = $client->getCrawler();
        self::assertCount(2, $streams = $crawler->filter('turbo-stream[action]'));
        self::assertCount(1, $navbar = $streams->filter('[action="replace"]'));
        self::assertSame('navbar', $navbar->attr('target'));
        self::assertCount(1, $seriesStream = $crawler->filter('[action="remove"]'));
        self::assertSame('series-'.$series->getId(), $seriesStream->attr('target'));

        $anime2rate->assertNotPersisted();
        self::assertSame(SeriesState::INCOMPLETE, $seriesRate->getState());
    }

    public function testObserveChecksCsrfToken(): void
    {
        $user = UserFactory::createOne();
        $anime = AnimeFactory::createOne();

        $client = self::createClient();
        $client->loginUser($user->object());
        $client->request('POST', sprintf('/animes/%d/observe', $anime->getId()));
        self::assertResponseIsUnprocessable();

        $client->request(
            'POST',
            sprintf('/animes/%d/observe', $anime->getId()),
            [Controller::COMMON_CSRF_TOKEN_FIELD => '123'],
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

        $client = self::createClient();
        $client->loginUser($user->object());
        self::requestWithCsrfToken('POST', sprintf('/animes/%s/observe', $anime->getId()));
        self::assertResponseRedirects('/');
        $client->followRedirect();

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
        $series = SeriesFactory::createOne();
        $anime = AnimeFactory::createOne(['series' => $series, 'status' => Status::RELEASED]);
        SeriesRateFactory::createOne(['user' => $user, 'series' => $series]);

        $client = self::createClient();
        $client->loginUser($user->object());
        self::requestWithCsrfToken('POST', sprintf('/animes/%s/observe', $anime->getId()));
        self::assertResponseRedirects('/');
        $client->followRedirect();

        self::assertHasFlashError('Anime was not skipped');
    }

    public function testObserveNotRatedSeries(): void
    {
        $user = UserFactory::createOne();
        $series = SeriesFactory::createOne();
        $anime = AnimeFactory::createOne(['series' => $series, 'status' => Status::RELEASED]);
        AnimeRateFactory::new()->skipped()->create(['user' => $user, 'anime' => $anime]);

        $client = self::createClient();
        $client->loginUser($user->object());
        self::requestWithCsrfToken('POST', sprintf('/animes/%s/observe', $anime->getId()));
        self::assertResponseRedirects('/');
        $client->followRedirect();

        self::assertHasFlashError('Can not observe anime in not rated series');
    }

    public function testObserveNotSeries(): void
    {
        $user = UserFactory::createOne();
        $anime = AnimeFactory::createOne(['status' => Status::RELEASED]);
        AnimeRateFactory::new()->skipped()->create(['user' => $user, 'anime' => $anime]);

        $client = self::createClient();
        $client->loginUser($user->object());
        self::requestWithCsrfToken('POST', sprintf('/animes/%s/observe', $anime->getId()));
        self::assertResponseRedirects('/');
        $client->followRedirect();

        self::assertHasFlashError('Oh no, anime has no series');
    }
}
