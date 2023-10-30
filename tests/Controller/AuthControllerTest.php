<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Tests\Factory\UserFactory;

final class AuthControllerTest extends ControllerTestCase
{
    public function testLogin(): void
    {
        UserFactory::createOne([
            'username' => $username = 'john',
            'password' => $password = 'qwerty',
        ]);

        $this->client->request('GET', '/login');
        self::assertResponseIsSuccessful();
        self::assertPageTitleContains('Login');

        $this->client->submitForm('Login', [
            '_username' => $username,
            '_password' => $password,
        ]);
        self::assertResponseRedirects('http://localhost/');
    }

    public function testLoginInvalidCredentials(): void
    {
        $this->client->request('GET', '/login');
        $this->client->submitForm('Login', [
            '_username' => 'unknown',
            '_password' => 'unknown',
        ]);
        self::assertResponseRedirects('http://localhost/login');

        $this->client->followRedirect();
        self::assertSelectorTextSame('div', 'Invalid credentials.');
    }

    public function testLoginRedirectAuthenticatedUserToIndex(): void
    {
        $user = UserFactory::createOne();
        $this->client->loginUser($user->object());

        $this->client->request('GET', '/login');
        self::assertResponseRedirects('/');
    }

    public function testLogout(): void
    {
        $user = UserFactory::createOne();
        $this->client->loginUser($user->object());

        $this->client->request('GET', '/logout');
        self::assertResponseRedirects('http://localhost/');

        $this->client->request('GET', '/login');
        self::assertResponseIsSuccessful();
    }
}
