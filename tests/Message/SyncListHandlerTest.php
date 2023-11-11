<?php

declare(strict_types=1);

namespace App\Tests\Message;

use App\Message\SyncList;
use App\Message\SyncListHandler;
use App\Message\SyncSeries;
use App\Shikimori\Api\Enum\UserAnimeStatus;
use App\Shikimori\Api\User\AnimeRatesRequest;
use App\Shikimori\Api\User\AnimeRatesResponse;
use App\Shikimori\Api\User\AnimeRatesResponseAnimeItem;
use App\Tests\Factory\AnimeFactory;
use App\Tests\Factory\AnimeRateFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\TestDouble\Shikimori\ShikimoriSpy;
use App\Tests\Trait\BaseAnimeDataUtil;
use DateTimeImmutable;
use Zenstruck\Messenger\Test\InteractsWithMessenger;

class SyncListHandlerTest extends MessageHandlerTestCase
{
    use BaseAnimeDataUtil;
    use InteractsWithMessenger;

    public function testHandle(): void
    {
        $user = UserFactory::new()->withLinkedAccount($accountId = 6610, $accessToken = '123')->create();
        $message = new SyncList($user->getId());

        $shikimori = self::getService(ShikimoriSpy::class);
        $shikimori->addRequest(
            new AnimeRatesRequest($accessToken, $accountId),
            [
                new AnimeRatesResponse(
                    id: $rateId = 123,
                    score: $rateScore = 6,
                    status: $rateStatus = UserAnimeStatus::WATCHING,
                    anime: $item = self::createAnimeItem(
                        AnimeRatesResponseAnimeItem::class,
                        $animeId = 456,
                        airedOn: new DateTimeImmutable('2007-01-02'),
                    ),
                ),
            ],
        );

        $handler = self::getService(SyncListHandler::class);
        ($handler)($message);

        $anime = AnimeFactory::find($animeId);
        self::assertBaseItemDataEqualsAnimeData($item, $anime->object());

        $rate = AnimeRateFactory::find($rateId);
        self::assertEquals($user->getId(), $rate->getUser()->getId());
        self::assertSame($rateScore, $rate->getScore());
        self::assertSame($rateStatus, $rate->getStatus());

        $messages = $this->transport('async')->queue()->messages(SyncSeries::class);
        self::assertCount(1, $messages);
        self::assertEquals($user->getId(), $messages[0]->userId);
    }
}
