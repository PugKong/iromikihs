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

        $client = self::getClient();
        $client->request('GET', '/login');
        self::assertResponseIsSuccessful();
        self::assertPageTitleContains('Login');

        $client->submitForm('Login', [
            '_username' => $username,
            '_password' => $password,
        ]);
        self::assertResponseRedirects('http://localhost/');
    }

    public function testLoginInvalidCredentials(): void
    {
        $client = self::getClient();
        $client->request('GET', '/login');
        $client->submitForm('Login', [
            '_username' => 'unknown',
            '_password' => 'unknown',
        ]);
        self::assertResponseRedirects('http://localhost/login');

        $client->followRedirect();
        self::assertSelectorTextSame('.alert-error', 'Invalid credentials.');
    }

    public function testLoginRedirectAuthenticatedUserToIndex(): void
    {
        $user = UserFactory::createOne();

        $client = self::getClient();
        $client->loginUser($user->object());
        $client->request('GET', '/login');
        self::assertResponseRedirects('/');
    }

    public function testLogout(): void
    {
        $user = UserFactory::createOne();

        $client = self::getClient();
        $client->loginUser($user->object());
        $client->request('GET', '/logout');
        self::assertResponseRedirects('http://localhost/');

        $client->request('GET', '/login');
        self::assertResponseIsSuccessful();
    }
}
