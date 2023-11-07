<?php

declare(strict_types=1);

namespace App\Tests\Service\Shikimori;

use App\Service\Shikimori\AnimeSeriesFetcher;
use App\Shikimori\Api\Anime\ItemRequest;
use App\Shikimori\Api\Anime\ItemResponse;
use App\Shikimori\Api\Anime\RelatedRequest;
use App\Shikimori\Api\Anime\RelatedResponse;
use App\Shikimori\Api\Anime\RelatedResponseAnimeItem;
use App\Shikimori\Api\BaseAnimeData;
use App\Tests\Factory\UserFactory;
use App\Tests\Service\ServiceTestCase;
use App\Tests\TestDouble\Shikimori\ShikimoriSpy;
use App\Tests\Trait\BaseAnimeDataUtil;

use const JSON_THROW_ON_ERROR;

final class AnimeSeriesFetcherTest extends ServiceTestCase
{
    use BaseAnimeDataUtil;

    /**
     * @dataProvider fetchSeriesProvider
     */
    public function testFetchSeries(callable $selectInitialId): void
    {
        $user = UserFactory::new()->withLinkedAccount()->create();

        $shikimori = self::getService(ShikimoriSpy::class);
        $token = UserFactory::DEFAULT_ACCESS_TOKEN;
        $other = new RelatedResponse('Other', null);

        $shikimori->addRequest(
            new ItemRequest($token, $firstId = 100),
            self::createAnimeItem(ItemResponse::class, $firstId),
        );
        $shikimori->addRequest(
            new ItemRequest($token, $secondId = 200),
            self::createAnimeItem(ItemResponse::class, $secondId),
        );
        $shikimori->addRequest(
            new ItemRequest($token, $thirdId = 300),
            self::createAnimeItem(ItemResponse::class, $thirdId),
        );

        $firstRelated = self::createAnimeItem(RelatedResponseAnimeItem::class, $firstId);
        $secondRelated = self::createAnimeItem(RelatedResponseAnimeItem::class, $secondId);
        $thirdRelated = self::createAnimeItem(RelatedResponseAnimeItem::class, $thirdId);

        $shikimori->addRequest(new RelatedRequest($token, $firstId), [
            new RelatedResponse(AnimeSeriesFetcher::SEQUEL, $secondRelated),
            $other,
        ]);
        $shikimori->addRequest(new RelatedRequest($token, $secondId), [
            $other,
            new RelatedResponse(AnimeSeriesFetcher::PREQUEL, $firstRelated),
            new RelatedResponse(AnimeSeriesFetcher::SEQUEL, $thirdRelated),
        ]);
        $shikimori->addRequest(new RelatedRequest($token, $thirdId), [
            new RelatedResponse(AnimeSeriesFetcher::PREQUEL, $secondRelated),
            $other,
        ]);

        $fetcher = self::getService(AnimeSeriesFetcher::class);
        $actual = ($fetcher)($user->object(), $selectInitialId([$firstId, $secondId, $thirdId]));

        $shikimori->assertCalls(4);

        // due to clown fiesta I did with BaseAnimeData, let's do some morning work-out
        $expected = [$firstRelated, $secondRelated, $thirdRelated];
        $cmp = fn (BaseAnimeData $a, BaseAnimeData $b) => $a->id <=> $b->id;
        usort($actual, $cmp);
        usort($expected, $cmp);
        self::assertSame(
            json_decode(json_encode($expected, JSON_THROW_ON_ERROR), true),
            json_decode(json_encode($actual, JSON_THROW_ON_ERROR), true),
        );
    }

    public static function fetchSeriesProvider(): array
    {
        return [
            'from first' => [fn (array $ids) => $ids[0]],
            'from second' => [fn (array $ids) => $ids[1]],
            'from third' => [fn (array $ids) => $ids[2]],
        ];
    }
}
