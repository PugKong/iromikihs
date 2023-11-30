<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\UserSyncState;
use App\Message\LinkAccountMessage;
use App\Tests\Factory\UserFactory;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
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

        $client = self::createClient();
        $client->loginUser($user->object());
        $client->request('GET', '/profile');

        self::assertResponseIsSuccessful();
        self::assertPageTitleContains('Profile');

        self::assertHasPageHeader('Profile');
        self::assertHasSyncStatusComponent();

        self::assertSelectorTextSame('span.account-info', "Shikimori account id is $accountId");
        self::assertSelectorExists('div.change-password');
    }

    public function testLinkAccount(): void
    {
        $user = UserFactory::createOne();

        $client = self::createClient();
        $client->loginUser($user->object());
        $client->request('GET', '/profile/link?code=the_code');
        self::assertResponseRedirects('/');

        self::assertSame(UserSyncState::LINK_ACCOUNT, $user->getSync()->getState());

        $messages = $this->transport('async')->queue()->messages(LinkAccountMessage::class);
        self::assertCount(1, $messages);
        self::assertEquals($user->getId(), $messages[0]->userId);
        self::assertSame('the_code', $messages[0]->code);
    }

    public function testLinkAccountChecksAccountLinkState(): void
    {
        $user = UserFactory::new()->withLinkedAccount()->create();

        $client = self::createClient();
        $client->loginUser($user->object());
        $client->request('GET', '/profile/link?code=the_code');
        self::assertResponseRedirects('/');

        $messages = $this->transport('async')->queue()->messages(LinkAccountMessage::class);
        self::assertCount(0, $messages);

        $client->followRedirect();
        self::assertHasFlashError('Account already linked.');
    }

    /**
     * @dataProvider linkAccountChecksSyncStateProvider
     */
    public function testLinkAccountChecksSyncState(?UserSyncState $state, bool $allowed): void
    {
        $user = UserFactory::new()->withSyncStatus($state)->create();

        $client = self::createClient();
        $client->loginUser($user->object());
        $client->request('GET', '/profile/link?code=the_code');
        self::assertResponseRedirects('/');

        $client->followRedirect();
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
        $user = UserFactory::createOne();

        $client = self::createClient();
        $client->loginUser($user->object());
        $client->request('GET', '/profile/link');
        self::assertResponseStatusCodeSame(404);
    }

    /**
     * @dataProvider linkAccountRemembersRedirectLocationProvider
     */
    public function testLinkAccountRemembersRedirectLocation(string $fromUrl): void
    {
        $user = UserFactory::createOne();

        $client = self::createClient();
        $client->loginUser($user->object());
        $client->request('GET', $fromUrl);
        $client->clickLink('Link your account');

        $linkQuery = http_build_query([
            'client_id' => 'shikimori_client_id',
            'redirect_uri' => 'http://localhost/profile/link',
            'response_type' => 'code',
        ]);
        $linkUrl = "https://shikimori.example.com/oauth/authorize?$linkQuery";
        self::assertResponseRedirects($linkUrl);

        $client->request('GET', '/profile/link?code=the_code');
        self::assertResponseRedirects($fromUrl);
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

    public function testChangePassword(): void
    {
        $user = UserFactory::createOne(['password' => $currentPassword = 'qwerty']);

        $client = self::createClient();
        $client->loginUser($user->object());
        $client->request('GET', '/profile');
        self::assertResponseIsSuccessful();

        $client->submitForm('Change password', [
            'change_password[currentPassword]' => $currentPassword,
            'change_password[password]' => $newPassword = 'th3 str0ngest p4ssw0rd',
            'change_password[passwordRepeat]' => $newPassword,
        ]);
        self::assertResponseRedirects('/profile');

        $hasher = self::getService(UserPasswordHasherInterface::class);
        self::assertTrue($hasher->isPasswordValid($user->object(), $newPassword));
    }

    /**
     * @dataProvider changePasswordValidationProvider
     */
    public function testChangePasswordValidation(
        array $expectedErrors,
        string $currentPassword,
        string $password,
        string $passwordRepeat,
    ): void {
        $user = UserFactory::createOne(['password' => $oldPassword = 'qwerty']);

        $client = self::createClient();
        $client->loginUser($user->object());
        $client->request('GET', '/profile');
        self::assertResponseIsSuccessful();

        $client->submitForm('Change password', [
            'change_password[currentPassword]' => $currentPassword,
            'change_password[password]' => $password,
            'change_password[passwordRepeat]' => $passwordRepeat,
        ]);
        self::assertResponseIsUnprocessable();

        $actualErrors = $client->getCrawler()->filter('ul.alert-error')->each(fn (Crawler $c) => $c->text());
        self::assertSame($expectedErrors, $actualErrors);

        $hasher = self::getService(UserPasswordHasherInterface::class);
        self::assertTrue($hasher->isPasswordValid($user->object(), $oldPassword));
    }

    public static function changePasswordValidationProvider(): array
    {
        $currentPassword = 'qwerty';
        $newPassword = 'th3 str0ngest p4ssw0rd';

        return [
            'blank current password' => [
                ['This value should be the user\'s current password.'],
                '',
                $newPassword,
                $newPassword,
            ],
            'current password do not match' => [
                ['This value should be the user\'s current password.'],
                'asdf',
                $newPassword,
                $newPassword,
            ],
            'blank new password' => [
                [
                    'The password strength is too low. Please use a stronger password.', // new password
                    'This value should not be blank.', // password repeat
                ],
                $currentPassword,
                '',
                '',
            ],
            'blank password repeat' => [
                ['This value should not be blank.'],
                $currentPassword,
                $newPassword,
                '',
            ],
            'password matches password repeat' => [
                ['Password does not match.'],
                $currentPassword,
                $newPassword,
                $currentPassword,
            ],
            'weak password' => [
                ['The password strength is too low. Please use a stronger password.'],
                $currentPassword,
                'qwerty',
                'qwerty',
            ],
            'only password repeat' => [
                ['The password strength is too low. Please use a stronger password.'],
                $currentPassword,
                '',
                $newPassword,
            ],
        ];
    }
}
