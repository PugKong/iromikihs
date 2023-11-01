<?php

declare(strict_types=1);

namespace App\Tests\Trait;

use App\Entity\Token;
use App\Service\Shikimori\TokenData;
use App\Service\Shikimori\TokenDataEncryptor;
use PHPUnit\Framework\Assert;

trait TokenUtil
{
    /**
     * @phpstan-template T of object
     *
     * @phpstan-param  class-string<T> $name
     *
     * @phpstan-return T
     */
    abstract protected static function getService(string $name): object;

    protected static function assertTokenData(TokenData $expected, Token $token): void
    {
        $actual = self::getService(TokenDataEncryptor::class)->decrypt($token->getData());

        Assert::assertEquals($expected, $actual);
    }
}
