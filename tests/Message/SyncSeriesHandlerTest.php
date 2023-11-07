<?php

declare(strict_types=1);

namespace App\Tests\Message;

use App\Message\SyncSeries;
use App\Message\SyncSeriesHandler;
use App\Shikimori\Api\Anime\ItemRequest;
use App\Shikimori\Api\Anime\ItemResponse;
use App\Shikimori\Api\Anime\RelatedRequest;
use App\Tests\Factory\AnimeFactory;
use App\Tests\Factory\AnimeRateFactory;
use App\Tests\Factory\SeriesFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\TestDouble\Shikimori\ShikimoriSpy;
use App\Tests\Trait\BaseAnimeDataUtil;
use Zenstruck\Messenger\Test\InteractsWithMessenger;

final class SyncSeriesHandlerTest extends MessageHandlerTestCase
{
    use BaseAnimeDataUtil;
    use InteractsWithMessenger;

    public function testSync(): void
    {
        $user = UserFactory::new()->withLinkedAccount(accessToken: $token = '123')->create();
        $anime = AnimeFactory::createOne();
        AnimeRateFactory::createOne(['user' => $user, 'anime' => $anime]);

        $shikimori = self::getService(ShikimoriSpy::class);
        $shikimori->addRequest(
            new ItemRequest($token, $anime->getId()),
            $item = $this->createAnimeItem(ItemResponse::class, $anime->getId()),
        );
        $shikimori->addRequest(
            new RelatedRequest($token, $anime->getId()),
            [],
        );

        $handler = self::getService(SyncSeriesHandler::class);
        ($handler)(new SyncSeries($user->getId()));

        self::assertCount(1, SeriesFactory::all());
        self::assertBaseItemDataEqualsAnimeData($item, $anime->object());

        $messages = $this->transport('async')->queue()->messages(SyncSeries::class);
        self::assertCount(1, $messages);
        self::assertEquals($user->getId(), $messages[0]->userId);
    }

    public function testStopRecursionWhenNothingToSync(): void
    {
        $user = UserFactory::new()->withLinkedAccount()->create();

        $handler = self::getService(SyncSeriesHandler::class);
        ($handler)(new SyncSeries($user->getId()));

        $messages = $this->transport('async')->queue()->messages(SyncSeries::class);
        self::assertCount(0, $messages);
    }
}
