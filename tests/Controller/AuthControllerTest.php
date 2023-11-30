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

        $client = self::createClient();
        $client->request('GET', '/login');
        self::assertResponseIsSuccessful();
        self::assertPageTitleContains('Login');

        $client->submitForm('Login', [
            '_username' => $username,
            '_password' => $password,
            '_remember_me' => false,
        ]);
        self::assertResponseRedirects('http://localhost/');
        self::assertNull($client->getCookieJar()->get('REMEMBERME'));
    }

    public function testLoginRememberMe(): void
    {
        UserFactory::createOne([
            'username' => $username = 'john',
            'password' => $password = 'qwerty',
        ]);

        $client = self::createClient();
        $client->request('GET', '/login');
        self::assertResponseIsSuccessful();

        $client->submitForm('Login', [
            '_username' => $username,
            '_password' => $password,
            '_remember_me' => true,
        ]);
        self::assertResponseRedirects('http://localhost/');
        self::assertNotNull($client->getCookieJar()->get('REMEMBERME'));
    }

    public function testLoginInvalidCredentials(): void
    {
        $client = self::createClient();
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

        $client = self::createClient();
        $client->loginUser($user->object());
        $client->request('GET', '/login');
        self::assertResponseRedirects('/');
    }

    public function testLogout(): void
    {
        $user = UserFactory::createOne();

        $client = self::createClient();
        $client->loginUser($user->object());
        $client->request('GET', '/logout');
        self::assertResponseRedirects('http://localhost/');

        $client->request('GET', '/login');
        self::assertResponseIsSuccessful();
    }
}
