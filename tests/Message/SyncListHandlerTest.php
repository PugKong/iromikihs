<?php

declare(strict_types=1);

namespace App\Tests\Message;

use App\Message\SyncList;
use App\Message\SyncListHandler;
use App\Shikimori\Api\Enum\Kind;
use App\Shikimori\Api\Enum\Status;
use App\Shikimori\Api\Enum\UserAnimeStatus;
use App\Shikimori\Api\User\AnimeItem;
use App\Shikimori\Api\User\AnimeRatesRequest;
use App\Shikimori\Api\User\AnimeRatesResponse;
use App\Tests\Factory\AnimeFactory;
use App\Tests\Factory\AnimeRateFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\TestDouble\Shikimori\ShikimoriStub;

class SyncListHandlerTest extends MessageHandlerTestCase
{
    public function testHandle(): void
    {
        $user = UserFactory::new()->withLinkedAccount($accountId = 6610, $accessToken = '123')->create();
        $message = new SyncList($user->getId());

        $shikimori = self::getService(ShikimoriStub::class);
        $shikimori->addRequest(
            new AnimeRatesRequest($accessToken, $accountId),
            [
                new AnimeRatesResponse(
                    id: $rateId = 123,
                    score: $rateScore = 6,
                    status: $rateStatus = UserAnimeStatus::WATCHING,
                    anime: new AnimeItem(
                        id: $animeId = 456,
                        name: $animeName = 'The anime',
                        url: $animeUrl = '/animes/456',
                        kind: $animeKind = Kind::MOVIE,
                        status: $animeStatus = Status::ONGOING,
                    ),
                ),
            ],
        );

        $handler = self::getService(SyncListHandler::class);
        ($handler)($message);

        $anime = AnimeFactory::find($animeId);
        self::assertSame($animeName, $anime->getName());
        self::assertSame($animeUrl, $anime->getUrl());
        self::assertSame($animeKind, $anime->getKind());
        self::assertSame($animeStatus, $anime->getStatus());

        $rate = AnimeRateFactory::find($rateId);
        self::assertTrue($user->getId()->equals($rate->getUser()->getId()));
        self::assertSame($rateScore, $rate->getScore());
        self::assertSame($rateStatus, $rate->getStatus());
    }
}
