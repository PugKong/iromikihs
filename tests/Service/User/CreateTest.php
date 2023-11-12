<?php

declare(strict_types=1);

namespace App\Tests\Service\User;

use App\Service\User\Create;
use App\Service\User\CreateData;
use App\Tests\Factory\UserFactory;
use App\Tests\Service\ServiceTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class CreateTest extends ServiceTestCase
{
    public function testCreateUser(): void
    {
        $create = self::getService(Create::class);
        ($create)(new CreateData($username = 'john', $password = 'qwerty'));

        $user = UserFactory::find(['username' => $username]);
        $hasher = self::getService(UserPasswordHasherInterface::class);
        self::assertTrue($hasher->isPasswordValid($user->object(), $password));
    }

    public function testCreateEmptySyncStatus(): void
    {
        $create = self::getService(Create::class);
        ($create)(new CreateData($username = 'john', 'qwerty'));

        $user = UserFactory::find(['username' => $username]);
        self::assertNull($user->getSync()->getAccountId());
        self::assertNull($user->getSync()->getToken());
        self::assertNull($user->getSync()->getState());
        self::assertNull($user->getSync()->getSyncedAt());
    }
}
