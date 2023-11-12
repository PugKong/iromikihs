<?php

declare(strict_types=1);

namespace App\Tests\Message;

use App\Entity\UserSyncState;
use App\Message\LinkAccountHandler;
use App\Message\LinkAccountMessage;
use App\Service\Shikimori\TokenData;
use App\Shikimori\Api\Auth\ExchangeCodeRequest;
use App\Shikimori\Api\Auth\TokenResponse;
use App\Shikimori\Api\Auth\WhoAmIRequest;
use App\Shikimori\Api\Auth\WhoAmIResponse;
use App\Tests\Factory\UserFactory;
use App\Tests\TestDouble\Shikimori\ShikimoriSpy;
use App\Tests\Trait\TokenUtil;
use DateTimeImmutable;
use Symfony\Component\Clock\Test\ClockSensitiveTrait;
use Symfony\Component\Uid\Uuid;

final class LinkAccountHandlerTest extends MessageHandlerTestCase
{
    use ClockSensitiveTrait;
    use TokenUtil;

    public function testLinkAccount(): void
    {
        $user = UserFactory::new()->withSyncStatus(state: UserSyncState::LINK_ACCOUNT)->create();

        $shikimori = self::getService(ShikimoriSpy::class);
        $shikimori->addRequest(
            new ExchangeCodeRequest($code = 'code'),
            new TokenResponse(
                accessToken: $accessToken = 'the access token',
                refreshToken: $refreshToken = 'the refresh token',
                createdAt: (new DateTimeImmutable('2007-01-02 03:04:05'))->getTimestamp(),
                expiresIn: 24 * 60 * 60,
            ),
        );
        $shikimori->addRequest(
            new WhoAmIRequest($accessToken),
            new WhoAmIResponse(id: $accountId = 6610),
        );

        self::mockTime(new DateTimeImmutable('2007-01-02 00:00:00'));
        $handler = self::getService(LinkAccountHandler::class);
        ($handler)(new LinkAccountMessage($user->getId(), $code));

        $sync = $user->getSync();
        self::assertSame($accountId, $sync->getAccountId());
        self::assertNull($sync->getState());

        self::assertTokenData(
            new TokenData(
                accessToken: $accessToken,
                refreshToken: $refreshToken,
                expiresAt: (new DateTimeImmutable('2007-01-03 03:04:05'))->getTimestamp(),
            ),
            $user->object(),
        );
    }

    public function testUserNotFound(): void
    {
        $handler = self::getService(LinkAccountHandler::class);
        ($handler)(new LinkAccountMessage(Uuid::v7(), '6610'));

        self::getService(ShikimoriSpy::class)->assertCalls(0);
    }

    /**
     * @dataProvider linkAccountInvalidSyncStateProvider
     */
    public function testInvalidSyncState(?UserSyncState $state): void
    {
        $user = UserFactory::new()->withSyncStatus(state: $state)->create();

        $handler = self::getService(LinkAccountHandler::class);
        ($handler)(new LinkAccountMessage($user->getId(), '6610'));

        self::getService(ShikimoriSpy::class)->assertCalls(0);
    }

    public static function linkAccountInvalidSyncStateProvider(): array
    {
        return [
            [null],
            [UserSyncState::ANIME_RATES],
            [UserSyncState::SERIES],
            [UserSyncState::SERIES_RATES],
            [UserSyncState::FAILED],
        ];
    }

    public function testAlreadyLinkedAccount(): void
    {
        $user = UserFactory::new()->withLinkedAccount()->withSyncStatus(state: UserSyncState::LINK_ACCOUNT)->create();

        $handler = self::getService(LinkAccountHandler::class);
        ($handler)(new LinkAccountMessage($user->getId(), '6610'));

        self::getService(ShikimoriSpy::class)->assertCalls(0);
    }
}
