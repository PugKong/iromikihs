<?php

declare(strict_types=1);

namespace App\Tests\Message;

use App\Message\LinkAccount;
use App\Message\LinkAccountHandler;
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

final class LinkAccountHandlerTest extends MessageHandlerTestCase
{
    use ClockSensitiveTrait;
    use TokenUtil;

    public function testLinkAccount(): void
    {
        $user = UserFactory::createOne();

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
        ($handler)(new LinkAccount($user->getId(), $code));

        self::assertSame($accountId, $user->getAccountId());

        self::assertTokenData(
            new TokenData(
                accessToken: $accessToken,
                refreshToken: $refreshToken,
                expiresAt: (new DateTimeImmutable('2007-01-03 03:04:05'))->getTimestamp(),
            ),
            $user->object(),
        );
    }
}
