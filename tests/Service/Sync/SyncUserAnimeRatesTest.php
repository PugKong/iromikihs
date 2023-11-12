<?php

declare(strict_types=1);

namespace App\Tests\Service\Sync;

use App\Entity\UserSyncState;
use App\Service\Sync\SyncUserAnimeRates;
use App\Shikimori\Api\Enum\Kind;
use App\Shikimori\Api\Enum\Status;
use App\Shikimori\Api\Enum\UserAnimeStatus;
use App\Shikimori\Api\User\AnimeRatesRequest;
use App\Shikimori\Api\User\AnimeRatesResponse;
use App\Shikimori\Api\User\AnimeRatesResponseAnimeItem;
use App\Tests\Factory\AnimeFactory;
use App\Tests\Factory\AnimeRateFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\Service\ServiceTestCase;
use App\Tests\TestDouble\Shikimori\ShikimoriSpy;
use App\Tests\Trait\BaseAnimeDataUtil;

final class SyncUserAnimeRatesTest extends ServiceTestCase
{
    use BaseAnimeDataUtil;

    public function testAddNewAnime(): void
    {
        $user = UserFactory::new()
            ->withLinkedAccount(accountId: $accountId = 6610, accessToken: $accessToken = 'the token')
            ->withSyncStatus(state: UserSyncState::ANIME_RATES)
            ->create()
        ;

        $shikimori = self::getService(ShikimoriSpy::class);
        $shikimori->addRequest(
            new AnimeRatesRequest($accessToken, $accountId),
            [
                self::dummyRateResponse(
                    $item = self::createAnimeItem(AnimeRatesResponseAnimeItem::class, $id = 6610),
                ),
            ],
        );

        $service = self::getService(SyncUserAnimeRates::class);
        ($service)($user->object());

        $anime = AnimeFactory::find($id);
        self::assertBaseItemDataEqualsAnimeData($item, $anime->object());
    }

    public function testUpdateAnime(): void
    {
        $user = UserFactory::new()
            ->withLinkedAccount(accountId: $accountId = 6610, accessToken: $accessToken = 'the token')
            ->withSyncStatus(state: UserSyncState::ANIME_RATES)
            ->create()
        ;

        $anime = AnimeFactory::createOne([
            'id' => $id = 6610,
            'name' => 'Old name',
            'url' => '/animes/6609',
            'kind' => Kind::OVA,
            'status' => Status::ANONS,
        ]);

        $shikimori = self::getService(ShikimoriSpy::class);
        $shikimori->addRequest(
            new AnimeRatesRequest($accessToken, $accountId),
            [
                self::dummyRateResponse(
                    $item = self::createAnimeItem(AnimeRatesResponseAnimeItem::class, $id),
                ),
            ],
        );

        $service = self::getService(SyncUserAnimeRates::class);
        ($service)($user->object());

        self::assertBaseItemDataEqualsAnimeData($item, $anime->object());
    }

    public function testAddRate(): void
    {
        $user = UserFactory::new()
            ->withLinkedAccount(accountId: $accountId = 6610, accessToken: $accessToken = 'the token')
            ->withSyncStatus(state: UserSyncState::ANIME_RATES)
            ->create()
        ;

        $shikimori = self::getService(ShikimoriSpy::class);
        $shikimori->addRequest(
            new AnimeRatesRequest($accessToken, $accountId),
            [
                new AnimeRatesResponse(
                    id: $id = 6610,
                    score: $score = 7,
                    status: $status = UserAnimeStatus::COMPLETED,
                    anime: self::createAnimeItem(AnimeRatesResponseAnimeItem::class, $animeId = 6611),
                ),
            ],
        );

        $service = self::getService(SyncUserAnimeRates::class);
        ($service)($user->object());

        $rate = AnimeRateFactory::find($id);
        self::assertEquals($user->getId(), $rate->getUser()->getId());
        self::assertSame($animeId, $rate->getAnime()->getId());
        self::assertSame($score, $rate->getScore());
        self::assertSame($status, $rate->getStatus());
    }

    public function testUpdateRate(): void
    {
        $user = UserFactory::new()
            ->withLinkedAccount(accountId: $accountId = 6610, accessToken: $accessToken = 'the token')
            ->withSyncStatus(state: UserSyncState::ANIME_RATES)
            ->create()
        ;
        $rate = AnimeRateFactory::createOne([
            'id' => $id = 6610,
            'user' => UserFactory::createOne(),
            'anime' => AnimeFactory::createOne(['id' => 6609]),
            'score' => 3,
            'status' => UserAnimeStatus::WATCHING,
        ]);

        $shikimori = self::getService(ShikimoriSpy::class);
        $shikimori->addRequest(
            new AnimeRatesRequest($accessToken, $accountId),
            [
                new AnimeRatesResponse(
                    id: $id,
                    score: $score = 7,
                    status: $status = UserAnimeStatus::COMPLETED,
                    anime: self::createAnimeItem(AnimeRatesResponseAnimeItem::class, $animeId = 6610),
                ),
            ],
        );

        $service = self::getService(SyncUserAnimeRates::class);
        ($service)($user->object());

        self::assertEquals($user->getId(), $rate->getUser()->getId());
        self::assertSame($animeId, $rate->getAnime()->getId());
        self::assertSame($score, $rate->getScore());
        self::assertSame($status, $rate->getStatus());
    }

    public function testDeleteRate(): void
    {
        $user = UserFactory::new()
            ->withLinkedAccount(accountId: $accountId = 6610, accessToken: $accessToken = 'the token')
            ->withSyncStatus(state: UserSyncState::ANIME_RATES)
            ->create()
        ;
        AnimeRateFactory::createOne(['user' => $user]);

        $shikimori = self::getService(ShikimoriSpy::class);
        $shikimori->addRequest(new AnimeRatesRequest($accessToken, $accountId), []);

        $service = self::getService(SyncUserAnimeRates::class);
        ($service)($user->object());

        self::assertCount(0, AnimeRateFactory::all());
    }

    public function testChangeSyncState(): void
    {
        $user = UserFactory::new()
            ->withLinkedAccount(accountId: $accountId = 6610, accessToken: $accessToken = 'the token')
            ->withSyncStatus(state: UserSyncState::ANIME_RATES)
            ->create()
        ;

        $shikimori = self::getService(ShikimoriSpy::class);
        $shikimori->addRequest(new AnimeRatesRequest($accessToken, $accountId), []);

        $service = self::getService(SyncUserAnimeRates::class);
        ($service)($user->object());

        self::assertSame(UserSyncState::SERIES, $user->getSync()->getState());
    }

    /**
     * @dataProvider invalidSyncStateProvider
     */
    public function testInvalidSyncState(?UserSyncState $state): void
    {
        $user = UserFactory::new()
            ->withLinkedAccount()
            ->withSyncStatus(state: $state)
            ->create()
        ;

        $service = self::getService(SyncUserAnimeRates::class);
        ($service)($user->object());

        self::assertSame($state, $user->getSync()->getState());
        self::getService(ShikimoriSpy::class)->assertCalls(0);
    }

    public static function invalidSyncStateProvider(): array
    {
        return [
            [null],
            [UserSyncState::SERIES],
            [UserSyncState::SERIES_RATES],
            [UserSyncState::LINK_ACCOUNT],
            [UserSyncState::FAILED],
        ];
    }

    private static function dummyRateResponse(AnimeRatesResponseAnimeItem $anime): AnimeRatesResponse
    {
        return new AnimeRatesResponse(
            id: 1,
            score: 2,
            status: UserAnimeStatus::DROPPED,
            anime: $anime,
        );
    }

    public function testNoLinkedAccount(): void
    {
        $user = UserFactory::new()->withSyncStatus(state: $state = UserSyncState::ANIME_RATES)->create();

        $service = self::getService(SyncUserAnimeRates::class);
        ($service)($user->object());

        self::assertSame($state, $user->getSync()->getState());
        self::getService(ShikimoriSpy::class)->assertCalls(0);
    }
}
