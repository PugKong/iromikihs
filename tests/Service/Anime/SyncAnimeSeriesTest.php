<?php

declare(strict_types=1);

namespace App\Tests\Service\Anime;

use App\Service\Anime\SyncAnimeSeries;
use App\Shikimori\Api\Enum\Kind;
use App\Shikimori\Api\Enum\Status;
use App\Tests\Factory\AnimeFactory;
use App\Tests\Factory\SeriesFactory;
use App\Tests\Service\ServiceTestCase;
use App\Tests\TestDouble\Shikimori\BaseAnimeDataStub;
use App\Tests\Trait\BaseAnimeDataUtil;
use DateTimeImmutable;
use Symfony\Component\Clock\Test\ClockSensitiveTrait;

final class SyncAnimeSeriesTest extends ServiceTestCase
{
    use BaseAnimeDataUtil;
    use ClockSensitiveTrait;

    public function testCreateNewSeries(): void
    {
        self::mockTime($updatedAt = new DateTimeImmutable('2007-01-02 03:04:05'));

        $animes = [
            $firstItem = $this->createAnimeItem(BaseAnimeDataStub::class, $firstId = 1),
            $secondItem = $this->createAnimeItem(BaseAnimeDataStub::class, $secondId = 2),
        ];

        $service = self::getService(SyncAnimeSeries::class);
        ($service)($animes);

        $series = SeriesFactory::all();
        self::assertCount(1, $series);
        $series = $series[0];
        self::assertEquals($updatedAt, $series->getUpdatedAt());

        $firstAnime = AnimeFactory::find($firstId);
        self::assertSame($series->object(), $firstAnime->getSeries());
        self::assertBaseItemDataEqualsAnimeData($firstItem, $firstAnime->object());

        $secondAnime = AnimeFactory::find($secondId);
        self::assertSame($series->object(), $secondAnime->getSeries());
        self::assertBaseItemDataEqualsAnimeData($secondItem, $secondAnime->object());
    }

    public function testUseExistingSeries(): void
    {
        self::mockTime($updatedAt = new DateTimeImmutable('2007-01-02 03:04:05'));

        $series = SeriesFactory::createOne(['updated_at' => new DateTimeImmutable('2006-01-02 03:04:05')]);

        $animes = [
            $firstItem = $this->createAnimeItem(BaseAnimeDataStub::class, $firstId = 10),
            $secondItem = $this->createAnimeItem(BaseAnimeDataStub::class, $secondId = 20),
        ];
        AnimeFactory::createOne([
            'id' => $firstId,
            'series' => $series,
            'name' => 'change me',
            'url' => '/animes/change-me',
            'kind' => Kind::MOVIE,
            'status' => Status::ANONS,
        ]);

        $service = self::getService(SyncAnimeSeries::class);
        ($service)($animes);

        self::assertCount(1, SeriesFactory::all());
        self::assertEquals($updatedAt, $series->getUpdatedAt());

        $firstAnime = AnimeFactory::find($firstId);
        self::assertSame($series->object(), $firstAnime->getSeries());
        self::assertBaseItemDataEqualsAnimeData($firstItem, $firstAnime->object());

        $secondAnime = AnimeFactory::find($secondId);
        self::assertSame($series->object(), $secondAnime->getSeries());
        self::assertBaseItemDataEqualsAnimeData($secondItem, $secondAnime->object());
    }
}
