<?php

declare(strict_types=1);

namespace App\Tests\Service\Shikimori;

use App\Service\Shikimori\ShikimoriAnimeSeriesFetcher;
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
use DateTimeImmutable;

use const JSON_THROW_ON_ERROR;

final class ShikimoriAnimeSeriesFetcherTest extends ServiceTestCase
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
        $otherWithAnimeData = new RelatedResponse(
            'Another other',
            self::createAnimeItem(RelatedResponseAnimeItem::class, 6610),
        );

        $shikimori->addRequest(
            new ItemRequest($token, $firstId = 100),
            self::createAnimeItem(
                ItemResponse::class,
                ...$firstItemArgs = [$firstId, 'airedOn' => new DateTimeImmutable('2007-01-02')],
            ),
        );
        $shikimori->addRequest(
            new ItemRequest($token, $secondId = 200),
            self::createAnimeItem(
                ItemResponse::class,
                ...$secondItemArgs = [$secondId, 'releasedOn' => new DateTimeImmutable('2008-01-02')],
            ),
        );
        $shikimori->addRequest(
            new ItemRequest($token, $thirdId = 300),
            self::createAnimeItem(ItemResponse::class, $thirdId),
        );

        $firstRelated = self::createAnimeItem(RelatedResponseAnimeItem::class, ...$firstItemArgs);
        $secondRelated = self::createAnimeItem(RelatedResponseAnimeItem::class, ...$secondItemArgs);
        $thirdRelated = self::createAnimeItem(RelatedResponseAnimeItem::class, $thirdId);

        $shikimori->addRequest(new RelatedRequest($token, $firstId), [
            new RelatedResponse(ShikimoriAnimeSeriesFetcher::SEQUEL, $secondRelated),
            $other,
            $otherWithAnimeData,
        ]);
        $shikimori->addRequest(new RelatedRequest($token, $secondId), [
            $other,
            $otherWithAnimeData,
            new RelatedResponse(ShikimoriAnimeSeriesFetcher::PREQUEL, $firstRelated),
            new RelatedResponse(ShikimoriAnimeSeriesFetcher::SEQUEL, $thirdRelated),
        ]);
        $shikimori->addRequest(new RelatedRequest($token, $thirdId), [
            new RelatedResponse(ShikimoriAnimeSeriesFetcher::PREQUEL, $secondRelated),
            $other,
            $otherWithAnimeData,
        ]);

        $fetcher = self::getService(ShikimoriAnimeSeriesFetcher::class);
        $result = ($fetcher)($user->object(), $selectInitialId([$firstId, $secondId, $thirdId]));

        self::assertSame('Anime', $result->seriesName);
        $shikimori->assertCalls(4);

        // due to clown fiesta I did with BaseAnimeData, let's do some morning work-out
        $actual = $result->animes;
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
