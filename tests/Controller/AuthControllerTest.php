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

        self::getClient()->request('GET', '/login');
        self::assertResponseIsSuccessful();
        self::assertPageTitleContains('Login');

        self::getClient()->submitForm('Login', [
            '_username' => $username,
            '_password' => $password,
        ]);
        self::assertResponseRedirects('http://localhost/');
    }

    public function testLoginInvalidCredentials(): void
    {
        self::getClient()->request('GET', '/login');
        self::getClient()->submitForm('Login', [
            '_username' => 'unknown',
            '_password' => 'unknown',
        ]);
        self::assertResponseRedirects('http://localhost/login');

        self::getClient()->followRedirect();
        self::assertSelectorTextSame('.alert-error', 'Invalid credentials.');
    }

    public function testLoginRedirectAuthenticatedUserToIndex(): void
    {
        self::getClient()
            ->loginUser(UserFactory::createOne()->object())
            ->request('GET', '/login')
        ;
        self::assertResponseRedirects('/');
    }

    public function testLogout(): void
    {
        self::getClient()
            ->loginUser(UserFactory::createOne()->object())
            ->request('GET', '/logout')
        ;
        self::assertResponseRedirects('http://localhost/');

        self::getClient()->request('GET', '/login');
        self::assertResponseIsSuccessful();
    }
}
