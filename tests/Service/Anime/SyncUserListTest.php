<?php

declare(strict_types=1);

namespace App\Tests\Service\Anime;

use App\Service\Anime\SyncUserList;
use App\Service\Anime\SyncUserListData;
use App\Shikimori\Api\Enum\Kind;
use App\Shikimori\Api\Enum\Status;
use App\Shikimori\Api\Enum\UserAnimeStatus;
use App\Shikimori\Api\User\AnimeRatesResponse;
use App\Shikimori\Api\User\AnimeRatesResponseAnimeItem;
use App\Tests\Factory\AnimeFactory;
use App\Tests\Factory\AnimeRateFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\Service\ServiceTestCase;
use App\Tests\Trait\BaseAnimeDataUtil;

final class SyncUserListTest extends ServiceTestCase
{
    use BaseAnimeDataUtil;

    public function testAddNewAnime(): void
    {
        $user = UserFactory::createOne();
        $rates = [];
        $rates[] = self::dummyRateResponse(
            $item = self::createAnimeItem(AnimeRatesResponseAnimeItem::class, $id = 6610),
        );

        $service = self::getService(SyncUserList::class);
        ($service)(new SyncUserListData($user->object(), $rates));

        $anime = AnimeFactory::find($id);
        self::assertBaseItemDataEqualsAnimeData($item, $anime->object());
    }

    public function testUpdateAnime(): void
    {
        $user = UserFactory::createOne();
        $anime = AnimeFactory::createOne([
            'id' => $id = 6610,
            'name' => 'Old name',
            'url' => '/animes/6609',
            'kind' => Kind::OVA,
            'status' => Status::ANONS,
        ]);

        $rates = [];
        $rates[] = self::dummyRateResponse(
            $item = self::createAnimeItem(AnimeRatesResponseAnimeItem::class, $id),
        );

        $service = self::getService(SyncUserList::class);
        ($service)(new SyncUserListData($user->object(), $rates));

        self::assertBaseItemDataEqualsAnimeData($item, $anime->object());
    }

    public function testAddRate(): void
    {
        $user = UserFactory::createOne();
        $rates = [];
        $rates[] = new AnimeRatesResponse(
            id: $id = 6610,
            score: $score = 7,
            status: $status = UserAnimeStatus::COMPLETED,
            anime: self::createAnimeItem(AnimeRatesResponseAnimeItem::class, $animeId = 6611),
        );

        $service = self::getService(SyncUserList::class);
        ($service)(new SyncUserListData($user->object(), $rates));

        $rate = AnimeRateFactory::find($id);
        self::assertEquals($user->getId(), $rate->getUser()->getId());
        self::assertSame($animeId, $rate->getAnime()->getId());
        self::assertSame($score, $rate->getScore());
        self::assertSame($status, $rate->getStatus());
    }

    public function testUpdateRate(): void
    {
        $user = UserFactory::createOne();
        $rate = AnimeRateFactory::createOne([
            'id' => $id = 6610,
            'user' => UserFactory::createOne(),
            'anime' => AnimeFactory::createOne(['id' => 6609]),
            'score' => 3,
            'status' => UserAnimeStatus::WATCHING,
        ]);

        $rates = [];
        $rates[] = new AnimeRatesResponse(
            id: $id,
            score: $score = 7,
            status: $status = UserAnimeStatus::COMPLETED,
            anime: self::createAnimeItem(AnimeRatesResponseAnimeItem::class, $animeId = 6610),
        );

        $service = self::getService(SyncUserList::class);
        ($service)(new SyncUserListData($user->object(), $rates));

        self::assertEquals($user->getId(), $rate->getUser()->getId());
        self::assertSame($animeId, $rate->getAnime()->getId());
        self::assertSame($score, $rate->getScore());
        self::assertSame($status, $rate->getStatus());
    }

    public function testDeleteRate(): void
    {
        $user = UserFactory::createOne();
        AnimeRateFactory::createOne(['user' => $user]);
        $rates = [];

        $service = self::getService(SyncUserList::class);
        ($service)(new SyncUserListData($user->object(), $rates));

        self::assertCount(0, AnimeRateFactory::all());
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
}
