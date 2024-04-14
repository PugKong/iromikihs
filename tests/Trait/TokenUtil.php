<?php

declare(strict_types=1);

namespace App\Tests\Trait;

use App\Entity\User;
use App\Service\Shikimori\TokenData;
use App\Service\Shikimori\TokenDataEncryptor;
use PHPUnit\Framework\Assert;

trait TokenUtil
{
    /**
     * @phpstan-template T of object
     *
     * @phpstan-param class-string<T> $name
     *
     * @phpstan-return T
     */
    abstract protected static function getService(string $name): object;

    public static function assertTokenData(TokenData $expected, User $user): void
    {
        $ciphertext = $user->getSync()->getToken();
        Assert::assertNotNull($ciphertext);
        $actual = self::getService(TokenDataEncryptor::class)->decrypt($ciphertext);

        Assert::assertEquals($expected, $actual);
    }
}
