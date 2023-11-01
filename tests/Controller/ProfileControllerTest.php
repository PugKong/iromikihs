<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Message\LinkAccount;
use App\Tests\Factory\UserFactory;
use Zenstruck\Messenger\Test\InteractsWithMessenger;

final class ProfileControllerTest extends ControllerTestCase
{
    use InteractsWithMessenger;

    public function testProfile(): void
    {
        $user = UserFactory::createOne(['accountId' => 6610]);

        $this->client->loginUser($user->object());
        $this->client->request('GET', '/profile');

        self::assertResponseIsSuccessful();
        self::assertPageTitleContains('Profile');
        self::assertSelectorTextSame('div', 'Shikimori account id is 6610');
    }

    public function testProfileShikimoriAccountNotLinked(): void
    {
        $this->client->loginUser(UserFactory::createOne()->object());
        $this->client->request('GET', '/profile');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextSame('div', 'Shikimori account is not linked');

        $accountLinkButton = $this->client->getCrawler()->filter('a.link-account');
        self::assertCount(1, $accountLinkButton);
        self::assertSame('Link your account', $accountLinkButton->text());
        $linkQuery = http_build_query([
            'client_id' => 'shikimori_client_id',
            'redirect_uri' => 'http://localhost/profile/link',
            'response_type' => 'code',
        ]);
        self::assertSame('https://shikimori.example.com/oauth/authorize?'.$linkQuery, $accountLinkButton->attr('href'));
    }

    public function testProfileRequiresAuthentication(): void
    {
        $this->client->request('GET', '/profile');
        self::assertResponseRedirects('http://localhost/login');
    }

    public function testLinkAccount(): void
    {
        $this->client->loginUser($user = UserFactory::createOne()->object());
        $this->client->request('GET', '/profile/link?code=the_code');
        self::assertResponseRedirects('/profile');

        $messages = $this->transport('async')->queue()->messages(LinkAccount::class);
        self::assertCount(1, $messages);
        self::assertTrue($user->getId()->equals($messages[0]->userId));
        self::assertSame('the_code', $messages[0]->code);

        $this->client->followRedirect();
        self::assertSelectorTextSame('.flash-notice', 'Your account will be linked soon.');
    }

    public function testLinkAccountRequiresAuthentication(): void
    {
        $this->client->request('GET', '/profile/link');
        self::assertResponseRedirects('http://localhost/login');
    }

    public function testLinkAccountRequiresCodeQueryParameter(): void
    {
        $this->client->loginUser(UserFactory::createOne()->object());
        $this->client->request('GET', '/profile/link');
        self::assertResponseStatusCodeSame(404);
    }
}
