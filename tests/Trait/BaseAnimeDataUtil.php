<?php

declare(strict_types=1);

namespace App\Tests\Trait;

use App\Entity\Anime;
use App\Shikimori\Api\BaseAnimeData;
use App\Shikimori\Api\Enum\Kind;
use App\Shikimori\Api\Enum\Status;
use DateTimeImmutable;
use PHPUnit\Framework\Assert;

trait BaseAnimeDataUtil
{
    /**
     * @phpstan-template T of BaseAnimeData
     *
     * @phpstan-param class-string<T> $class
     *
     * @phpstan-return T
     */
    public static function createAnimeItem(
        string $class,
        int $id,
        ?DateTimeImmutable $airedOn = null,
        ?DateTimeImmutable $releasedOn = null,
    ): BaseAnimeData {
        return new $class(
            id: $id,
            name: "Anime $id",
            url: "/animes/$id",
            kind: Kind::TV,
            status: Status::RELEASED,
            airedOn: $airedOn,
            releasedOn: $releasedOn,
        );
    }

    public static function assertBaseItemDataEqualsAnimeData(BaseAnimeData $item, Anime $anime): void
    {
        Assert::assertSame($item->name, $anime->getName());
        Assert::assertSame($item->url, $anime->getUrl());
        Assert::assertSame($item->kind, $anime->getKind());
        Assert::assertSame($item->status, $anime->getStatus());
        Assert::assertEquals($item->airedOn, $anime->getAiredOn());
        Assert::assertEquals($item->releasedOn, $anime->getReleasedOn());
    }
}
