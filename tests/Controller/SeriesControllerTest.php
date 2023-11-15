<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\SeriesState;
use App\Entity\UserSyncState;
use App\Shikimori\Api\Enum\Status;
use App\Shikimori\Api\Enum\UserAnimeStatus;
use App\Tests\Factory\AnimeFactory;
use App\Tests\Factory\AnimeRateFactory;
use App\Tests\Factory\SeriesFactory;
use App\Tests\Factory\SeriesRateFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\Twig\Component\CsrfTokenManagerSpy;
use App\Twig\Component\SimpleForm;

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

    public function testDropped(): void
    {
        $user = UserFactory::createOne();
        $series = SeriesFactory::createOne();
        $anime1 = AnimeFactory::createOne(['series' => $series]);
        $anime2 = AnimeFactory::createOne(['series' => $series]);
        AnimeRateFactory::createOne(['user' => $user, 'anime' => $anime1]);
        AnimeRateFactory::createOne(['user' => $user, 'anime' => $anime2]);
        SeriesRateFactory::createOne(['user' => $user, 'series' => $series, 'state' => SeriesState::DROPPED]);

        self::getClient()
            ->loginUser($user->object())
            ->request('GET', '/series/dropped')
        ;
        self::assertResponseIsSuccessful();
        self::assertPageTitleContains('Dropped series');

        self::assertHasPageHeader('Dropped series');
        self::assertHasSyncStatusComponent();

        $sections = self::getClient()->getCrawler()->filter('section.series-list');
        self::assertCount(1, $sections);

        $listItems = $sections->first()->filter('div.series-list-item');
        self::assertCount(2, $listItems);
    }

    /**
     * @dataProvider dropProvider
     */
    public function testDrop(string $fromUrl, SeriesState $state): void
    {
        $user = UserFactory::createOne();
        $series = SeriesFactory::createOne();
        $seriesRate = SeriesRateFactory::createOne([
            'user' => $user,
            'series' => $series,
            'state' => $state,
        ]);

        self::getClient()
            ->loginUser($user->object())
            ->request('GET', $fromUrl)
        ;
        self::assertResponseIsSuccessful();

        self::getClient()->submitForm('Drop series');
        self::assertResponseRedirects('http://localhost'.$fromUrl);

        self::assertSame(SeriesState::DROPPED, $seriesRate->getState());
    }

    public static function dropProvider(): array
    {
        return [
            'incomplete list' => ['/series/incomplete', SeriesState::INCOMPLETE],
            'complete list' => ['/series/complete', SeriesState::COMPLETE],
        ];
    }

    public function testDropChecksCsrfToken(): void
    {
        $user = UserFactory::createOne();
        $series = SeriesFactory::createOne();
        $seriesRate = SeriesRateFactory::createOne([
            'user' => $user,
            'series' => $series,
            'state' => SeriesState::INCOMPLETE,
        ]);

        self::getClient()
            ->loginUser($user->object())
            ->request('POST', sprintf('/series/rates/%s/drop', $seriesRate->getId()))
        ;
        self::assertResponseIsUnprocessable();

        self::getClient()->request(
            'POST',
            sprintf('/series/rates/%s/drop', $seriesRate->getId()),
            [SimpleForm::CSRF_TOKEN_FIELD => '123'],
        );
        self::assertResponseIsUnprocessable();
    }

    /**
     * @dataProvider checksSyncStatusProvider
     */
    public function testDropChecksSyncStatus(?UserSyncState $state, bool $allowed): void
    {
        $user = UserFactory::new()->withSyncStatus(state: $state)->create();
        $series = SeriesFactory::createOne();
        $seriesRate = SeriesRateFactory::createOne([
            'user' => $user,
            'series' => $series,
            'state' => SeriesState::INCOMPLETE,
        ]);

        $csrfTokenManager = new CsrfTokenManagerSpy([SimpleForm::CSRF_TOKEN_ID => $csrfToken = '123']);
        $csrfTokenManager->register(self::getContainer());

        self::getClient()
            ->loginUser($user->object())
            ->request(
                'POST',
                sprintf('/series/rates/%s/drop', $seriesRate->getId()),
                [SimpleForm::CSRF_TOKEN_FIELD => $csrfToken],
            )
        ;
        self::assertResponseRedirects('/');
        self::getClient()->followRedirect();

        $error = 'Can not drop series while syncing data';
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

    public function testDropOnlyOwnSeries(): void
    {
        $user = UserFactory::createOne();
        $series = SeriesFactory::createOne();
        $seriesRate = SeriesRateFactory::createOne([
            'user' => $user,
            'series' => $series,
            'state' => SeriesState::INCOMPLETE,
        ]);

        $csrfTokenManager = new CsrfTokenManagerSpy([SimpleForm::CSRF_TOKEN_ID => $csrfToken = '123']);
        $csrfTokenManager->register(self::getContainer());

        self::getClient()
            ->loginUser(UserFactory::createOne()->object())
            ->request(
                'POST',
                sprintf('/series/rates/%s/drop', $seriesRate->getId()),
                [SimpleForm::CSRF_TOKEN_FIELD => $csrfToken],
            )
        ;
        self::assertResponseStatusCodeSame(403);
    }

    public function testRestoreIncomplete(): void
    {
        $user = UserFactory::createOne();
        $series = SeriesFactory::createOne();
        $anime1 = AnimeFactory::createOne(['series' => $series, 'status' => Status::RELEASED]);
        $anime2 = AnimeFactory::createOne(['series' => $series, 'status' => Status::RELEASED]);
        AnimeRateFactory::createOne(['user' => $user, 'anime' => $anime1, 'status' => UserAnimeStatus::COMPLETED]);
        AnimeRateFactory::createOne(['user' => $user, 'anime' => $anime2, 'status' => UserAnimeStatus::PLANNED]);
        $seriesRate = SeriesRateFactory::createOne([
            'user' => $user,
            'series' => $series,
            'state' => SeriesState::DROPPED,
        ]);

        self::getClient()
            ->loginUser($user->object())
            ->request('GET', $fromUrl = '/series/dropped')
        ;
        self::assertResponseIsSuccessful();

        self::getClient()->submitForm('Restore series');
        self::assertResponseRedirects('http://localhost'.$fromUrl);

        self::assertSame(SeriesState::INCOMPLETE, $seriesRate->getState());
    }

    public function testRestoreComplete(): void
    {
        $user = UserFactory::createOne();
        $series = SeriesFactory::createOne();
        $anime1 = AnimeFactory::createOne(['series' => $series, 'status' => Status::RELEASED]);
        $anime2 = AnimeFactory::createOne(['series' => $series, 'status' => Status::RELEASED]);
        AnimeRateFactory::createOne(['user' => $user, 'anime' => $anime1, 'status' => UserAnimeStatus::COMPLETED]);
        AnimeRateFactory::createOne(['user' => $user, 'anime' => $anime2, 'status' => UserAnimeStatus::COMPLETED]);
        $seriesRate = SeriesRateFactory::createOne([
            'user' => $user,
            'series' => $series,
            'state' => SeriesState::DROPPED,
        ]);

        self::getClient()
            ->loginUser($user->object())
            ->request('GET', $fromUrl = '/series/dropped')
        ;
        self::assertResponseIsSuccessful();

        self::getClient()->submitForm('Restore series');
        self::assertResponseRedirects('http://localhost'.$fromUrl);

        self::assertSame(SeriesState::COMPLETE, $seriesRate->getState());
    }

    public function testRestoreChecksCsrfToken(): void
    {
        $user = UserFactory::createOne();
        $series = SeriesFactory::createOne();
        $seriesRate = SeriesRateFactory::createOne([
            'user' => $user,
            'series' => $series,
            'state' => SeriesState::DROPPED,
        ]);

        self::getClient()
            ->loginUser($user->object())
            ->request('POST', sprintf('/series/rates/%s/restore', $seriesRate->getId()))
        ;
        self::assertResponseIsUnprocessable();

        self::getClient()->request(
            'POST',
            sprintf('/series/rates/%s/restore', $seriesRate->getId()),
            [SimpleForm::CSRF_TOKEN_FIELD => '123'],
        );
        self::assertResponseIsUnprocessable();
    }

    /**
     * @dataProvider checksSyncStatusProvider
     */
    public function testRestoreChecksSyncStatus(?UserSyncState $state, bool $allowed): void
    {
        $user = UserFactory::new()->withSyncStatus(state: $state)->create();
        $series = SeriesFactory::createOne();
        $seriesRate = SeriesRateFactory::createOne([
            'user' => $user,
            'series' => $series,
            'state' => SeriesState::DROPPED,
        ]);

        $csrfTokenManager = new CsrfTokenManagerSpy([SimpleForm::CSRF_TOKEN_ID => $csrfToken = '123']);
        $csrfTokenManager->register(self::getContainer());

        self::getClient()
            ->loginUser($user->object())
            ->request(
                'POST',
                sprintf('/series/rates/%s/restore', $seriesRate->getId()),
                [SimpleForm::CSRF_TOKEN_FIELD => $csrfToken],
            )
        ;
        self::assertResponseRedirects('/');
        self::getClient()->followRedirect();

        $error = 'Can not restore series while syncing data';
        if ($allowed) {
            self::assertHasNoFlashError($error);
        } else {
            self::assertHasFlashError($error);
        }
    }

    public function testRestoreOnlyOwnSeries(): void
    {
        $user = UserFactory::createOne();
        $series = SeriesFactory::createOne();
        $seriesRate = SeriesRateFactory::createOne([
            'user' => $user,
            'series' => $series,
            'state' => SeriesState::DROPPED,
        ]);

        $csrfTokenManager = new CsrfTokenManagerSpy([SimpleForm::CSRF_TOKEN_ID => $csrfToken = '123']);
        $csrfTokenManager->register(self::getContainer());

        self::getClient()
            ->loginUser(UserFactory::createOne()->object())
            ->request(
                'POST',
                sprintf('/series/rates/%s/restore', $seriesRate->getId()),
                [SimpleForm::CSRF_TOKEN_FIELD => $csrfToken],
            )
        ;
        self::assertResponseStatusCodeSame(403);
    }
}
