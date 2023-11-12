<?php

declare(strict_types=1);

namespace App\Tests\Service\Shikimori;

use App\Service\Shikimori\TokenData;
use App\Service\Shikimori\TokenStorage;
use App\Shikimori\Api\Auth\RefreshTokenRequest;
use App\Shikimori\Api\Auth\TokenResponse;
use App\Tests\Factory\UserFactory;
use App\Tests\Service\ServiceTestCase;
use App\Tests\TestDouble\Shikimori\ShikimoriSpy;
use App\Tests\Trait\TokenUtil;
use DateTimeImmutable;
use Symfony\Component\Clock\Test\ClockSensitiveTrait;

final class TokenStorageTest extends ServiceTestCase
{
    use ClockSensitiveTrait;
    use TokenUtil;

    public function testStore(): void
    {
        $user = UserFactory::createOne();

        $storage = self::getService(TokenStorage::class);
        $storage->store(
            $user->object(),
            new TokenResponse(
                accessToken: $accessToken = 'the access token',
                refreshToken: $refreshToken = 'the refresh token',
                createdAt: (new DateTimeImmutable('2007-01-02 03:04:05'))->getTimestamp(),
                expiresIn: 24 * 60 * 60,
            ),
        );

        self::assertTokenData(
            new TokenData(
                accessToken: $accessToken,
                refreshToken: $refreshToken,
                expiresAt: (new DateTimeImmutable('2007-01-03 03:04:05'))->getTimestamp(),
            ),
            $user->object(),
        );
    }

    public function testStoreReplace(): void
    {
        $user = UserFactory::new()->withLinkedAccount()->create();

        $storage = self::getService(TokenStorage::class);
        $storage->store(
            $user->object(),
            new TokenResponse(
                accessToken: $accessToken = 'the access token',
                refreshToken: $refreshToken = 'the refresh token',
                createdAt: (new DateTimeImmutable('2007-01-02 03:04:05'))->getTimestamp(),
                expiresIn: 24 * 60 * 60,
            ),
        );

        self::assertTokenData(
            new TokenData(
                accessToken: $accessToken,
                refreshToken: $refreshToken,
                expiresAt: (new DateTimeImmutable('2007-01-03 03:04:05'))->getTimestamp(),
            ),
            $user->object(),
        );
    }

    public function testRetrieve(): void
    {
        self::mockTime(new DateTimeImmutable('2007-01-03 03:04:05'));

        $user = UserFactory::new()->withLinkedAccount(accessToken: $expectedToken = 'the access token')->create();

        $actualToken = self::getService(TokenStorage::class)->retrieve($user->object());
        self::assertSame($expectedToken, $actualToken);
    }

    public function testRetrieveRefreshToken(): void
    {
        self::mockTime(new DateTimeImmutable('2007-01-03 03:04:05'));

        $user = UserFactory::new()
            ->withLinkedAccount(
                accessToken: 'expired access token',
                refreshToken: $oldRefreshToken = 'the refresh token',
                expiresAt: (new DateTimeImmutable('2007-01-03 03:05:04')),
            )
            ->create()
        ;

        $shikimori = self::getService(ShikimoriSpy::class);
        $shikimori->addRequest(
            new RefreshTokenRequest($oldRefreshToken),
            new TokenResponse(
                accessToken: $newAccessToken = 'the access token',
                refreshToken: $newRefreshToken = 'the refresh token',
                createdAt: (new DateTimeImmutable('2007-01-03 03:04:05'))->getTimestamp(),
                expiresIn: 24 * 60 * 60,
            ),
        );

        $actualToken = self::getService(TokenStorage::class)->retrieve($user->object());
        self::assertSame($newAccessToken, $actualToken);

        self::assertTokenData(
            new TokenData(
                accessToken: $newAccessToken,
                refreshToken: $newRefreshToken,
                expiresAt: (new DateTimeImmutable('2007-01-04 03:04:05'))->getTimestamp(),
            ),
            $user->object(),
        );
    }
}
