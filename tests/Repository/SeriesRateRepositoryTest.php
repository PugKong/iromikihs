<?php

declare(strict_types=1);

namespace App\Tests\Repository;

use App\Repository\SeriesRateRepository;
use App\Tests\Factory\SeriesRateFactory;
use App\Tests\Factory\UserFactory;

final class SeriesRateRepositoryTest extends RepositoryTestCase
{
    public function testFindOtherByUser(): void
    {
        $targetUser = UserFactory::createOne();
        $targetRates = SeriesRateFactory::createMany(2, ['user' => $targetUser]);
        SeriesRateFactory::createMany(2, ['user' => UserFactory::new()]);

        $repository = self::getService(SeriesRateRepository::class);
        $actual = $repository->findOtherByUser($targetUser->object(), []);

        self::assertCount(2, $actual);
        self::assertContains($targetRates[0]->object(), $actual);
        self::assertContains($targetRates[1]->object(), $actual);

        $actual = $repository->findOtherByUser($targetUser->object(), [$targetRates[0]->object()]);

        self::assertCount(1, $actual);
        self::assertContains($targetRates[1]->object(), $actual);
    }
}
