<?php

declare(strict_types=1);

namespace App\Tests\Service\Anime;

use App\Service\Anime\SyncUserList;
use App\Service\Anime\SyncUserListData;
use App\Shikimori\Api\Enum\Kind;
use App\Shikimori\Api\Enum\Status;
use App\Shikimori\Api\Enum\UserAnimeStatus;
use App\Shikimori\Api\User\AnimeItem;
use App\Shikimori\Api\User\AnimeRatesResponse;
use App\Tests\Factory\AnimeFactory;
use App\Tests\Factory\AnimeRateFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\Service\ServiceTestCase;

final class SyncUserListTest extends ServiceTestCase
{
    public function testAddNewAnime(): void
    {
        $user = UserFactory::createOne();
        $rates = [];
        $rates[] = self::dummyRateResponse(new AnimeItem(
            id: $id = 6610,
            name: $name = 'Anime 6610',
            url: $url = '/animes/6610',
            kind: $kind = Kind::MOVIE,
            status: $status = Status::RELEASED,
        ));

        $service = self::getService(SyncUserList::class);
        ($service)(new SyncUserListData($user->object(), $rates));

        $anime = AnimeFactory::find($id);
        self::assertSame($name, $anime->getName());
        self::assertSame($url, $anime->getUrl());
        self::assertSame($kind, $anime->getKind());
        self::assertSame($status, $anime->getStatus());
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
        $rates[] = self::dummyRateResponse(new AnimeItem(
            id: $id,
            name: $name = 'Anime 6610',
            url: $url = '/animes/6610',
            kind: $kind = Kind::MOVIE,
            status: $status = Status::RELEASED,
        ));

        $service = self::getService(SyncUserList::class);
        ($service)(new SyncUserListData($user->object(), $rates));

        self::assertSame($name, $anime->getName());
        self::assertSame($url, $anime->getUrl());
        self::assertSame($kind, $anime->getKind());
        self::assertSame($status, $anime->getStatus());
    }

    public function testAddRate(): void
    {
        $user = UserFactory::createOne();
        $rates = [];
        $rates[] = new AnimeRatesResponse(
            id: $id = 6610,
            score: $score = 7,
            status: $status = UserAnimeStatus::COMPLETED,
            anime: self::dummyAnimeItem($animeId = 6611),
        );

        $service = self::getService(SyncUserList::class);
        ($service)(new SyncUserListData($user->object(), $rates));

        $rate = AnimeRateFactory::find($id);
        self::assertTrue($user->getId()->equals($rate->getUser()->getId()));
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
            anime: self::dummyAnimeItem($animeId = 6611),
        );

        $service = self::getService(SyncUserList::class);
        ($service)(new SyncUserListData($user->object(), $rates));

        self::assertTrue($user->getId()->equals($rate->getUser()->getId()));
        self::assertSame($animeId, $rate->getAnime()->getId());
        self::assertSame($score, $rate->getScore());
        self::assertSame($status, $rate->getStatus());
    }

    public function testDeleteRate(): void
    {
        $user = UserFactory::createOne();
        AnimeRateFactory::createOne([
            'id' => 6610,
            'user' => $user,
            'anime' => AnimeFactory::createOne(['id' => 6609]),
            'score' => 3,
            'status' => UserAnimeStatus::WATCHING,
        ]);
        $rates = [];

        $service = self::getService(SyncUserList::class);
        ($service)(new SyncUserListData($user->object(), $rates));

        self::assertCount(0, AnimeRateFactory::all());
    }

    private static function dummyRateResponse(AnimeItem $anime): AnimeRatesResponse
    {
        return new AnimeRatesResponse(
            id: 1,
            score: 2,
            status: UserAnimeStatus::DROPPED,
            anime: $anime,
        );
    }

    private static function dummyAnimeItem(int $id): AnimeItem
    {
        return new AnimeItem(
            id: $id,
            name: 'Anime 6610',
            url: '/animes/6610',
            kind: Kind::MOVIE,
            status: Status::RELEASED,
        );
    }
}
