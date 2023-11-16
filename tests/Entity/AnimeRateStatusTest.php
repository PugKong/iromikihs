<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\AnimeRateStatus;
use App\Shikimori\Api\Enum\UserAnimeStatus;
use PHPUnit\Framework\TestCase;

final class AnimeRateStatusTest extends TestCase
{
    public function testFromUserAnimeStatus(): void
    {
        foreach (UserAnimeStatus::cases() as $case) {
            $actual = AnimeRateStatus::fromUserAnimeStatus($case);
            self::assertSame($case->value, $actual->value);
        }
    }
}
