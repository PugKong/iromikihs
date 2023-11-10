<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Message\LinkAccount;
use App\Tests\Factory\UserFactory;
use Zenstruck\Messenger\Test\InteractsWithMessenger;

final class ProfileControllerTest extends ControllerTestCase
{
    use InteractsWithMessenger;

    /**
     * @dataProvider requiresAuthenticationProvider
     */
    public function testRequiresAuthentication(string $method, string $uri): void
    {
        self::assertRequiresAuthentication($method, $uri);
    }

    public static function requiresAuthenticationProvider(): array
    {
        return [
            ['GET', '/profile'],
            ['GET', '/profile/link'],
        ];
    }

    public function testProfile(): void
    {
        $user = UserFactory::new()->withLinkedAccount($accountId = 6610)->create();

        self::getClient()
            ->loginUser($user->object())
            ->request('GET', '/profile')
        ;

        self::assertResponseIsSuccessful();
        self::assertPageTitleContains('Profile');

        self::assertHasNoAccountLinkSection();
        self::assertSelectorTextSame('main div', "Shikimori account id is $accountId");
    }

    public function testProfileShikimoriAccountNotLinked(): void
    {
        self::getClient()
            ->loginUser(UserFactory::createOne()->object())
            ->request('GET', '/profile')
        ;

        self::assertResponseIsSuccessful();
        self::assertHasAccountLinkSection();
    }

    public function testLinkAccount(): void
    {
        self::getClient()
            ->loginUser($user = UserFactory::createOne()->object())
            ->request('GET', '/profile/link?code=the_code')
        ;
        self::assertResponseRedirects('/profile');

        $messages = $this->transport('async')->queue()->messages(LinkAccount::class);
        self::assertCount(1, $messages);
        self::assertEquals($user->getId(), $messages[0]->userId);
        self::assertSame('the_code', $messages[0]->code);

        self::getClient()->followRedirect();
        self::assertSelectorTextSame('.flash-notice', 'Your account will be linked soon.');
        self::assertHasAccountLinkSection();
    }

    public function testLinkAccountRequiresCodeQueryParameter(): void
    {
        self::getClient()
            ->loginUser(UserFactory::createOne()->object())
            ->request('GET', '/profile/link')
        ;
        self::assertResponseStatusCodeSame(404);
    }
}
