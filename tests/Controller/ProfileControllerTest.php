<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\UserSyncState;
use App\Message\LinkAccountMessage;
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
            ['GET', '/profile/link/start'],
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

        self::assertHasPageHeader('Profile');
        self::assertHasSyncStatusComponent();

        self::assertSelectorTextSame('main div', "Shikimori account id is $accountId");
    }

    public function testLinkAccount(): void
    {
        self::getClient()
            ->loginUser($user = UserFactory::createOne()->object())
            ->request('GET', '/profile/link?code=the_code')
        ;
        self::assertResponseRedirects('/');

        self::assertSame(UserSyncState::LINK_ACCOUNT, $user->getSync()->getState());

        $messages = $this->transport('async')->queue()->messages(LinkAccountMessage::class);
        self::assertCount(1, $messages);
        self::assertEquals($user->getId(), $messages[0]->userId);
        self::assertSame('the_code', $messages[0]->code);
    }

    public function testLinkAccountChecksAccountLinkState(): void
    {
        self::getClient()
            ->loginUser(UserFactory::new()->withLinkedAccount()->create()->object())
            ->request('GET', '/profile/link?code=the_code')
        ;
        self::assertResponseRedirects('/');

        $messages = $this->transport('async')->queue()->messages(LinkAccountMessage::class);
        self::assertCount(0, $messages);

        self::getClient()->followRedirect();
        self::assertHasFlashError('Account already linked.');
    }

    /**
     * @dataProvider linkAccountChecksSyncStateProvider
     */
    public function testLinkAccountChecksSyncState(?UserSyncState $state, bool $allowed): void
    {
        self::getClient()
            ->loginUser(UserFactory::new()->withSyncStatus($state)->create()->object())
            ->request('GET', '/profile/link?code=the_code')
        ;
        self::assertResponseRedirects('/');

        self::getClient()->followRedirect();
        if ($allowed) {
            self::assertHasNoFlashError('Can not link account due to active sync.');
        } else {
            self::assertHasFlashError('Can not link account due to active sync.');
        }
    }

    public static function linkAccountChecksSyncStateProvider(): array
    {
        return [
            [null, true],
            [UserSyncState::FAILED, true],
            [UserSyncState::ANIME_RATES, false],
            [UserSyncState::SERIES, false],
            [UserSyncState::SERIES_RATES, false],
        ];
    }

    public function testLinkAccountRequiresCodeQueryParameter(): void
    {
        self::getClient()
            ->loginUser(UserFactory::createOne()->object())
            ->request('GET', '/profile/link')
        ;
        self::assertResponseStatusCodeSame(404);
    }

    /**
     * @dataProvider linkAccountRemembersRedirectLocationProvider
     */
    public function testLinkAccountRemembersRedirectLocation(string $fromUrl): void
    {
        self::getClient()
            ->loginUser(UserFactory::createOne()->object())
            ->request('GET', $fromUrl)
        ;
        self::getClient()->clickLink('Link your account');

        $linkQuery = http_build_query([
            'client_id' => 'shikimori_client_id',
            'redirect_uri' => 'http://localhost/profile/link',
            'response_type' => 'code',
        ]);
        $linkUrl = "https://shikimori.example.com/oauth/authorize?$linkQuery";
        self::assertResponseRedirects($linkUrl);

        self::getClient()->request('GET', '/profile/link?code=the_code');
        self::assertResponseRedirects('http://localhost'.$fromUrl);
    }

    public static function linkAccountRemembersRedirectLocationProvider(): array
    {
        return [
            ['/'],
            ['/series/incomplete'],
            ['/series/complete'],
            ['/series/dropped'],
            ['/profile'],
        ];
    }
}
