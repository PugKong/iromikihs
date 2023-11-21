<?php

declare(strict_types=1);

namespace App\Tests\Repository;

use App\Repository\UserRepository;
use App\Tests\Factory\UserFactory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class UserRepositoryTest extends RepositoryTestCase
{
    public function testUpgradePassword(): void
    {
        $user = UserFactory::createOne();

        $hasher = self::getService(UserPasswordHasherInterface::class);
        $newPassword = 'asdf';

        $repository = self::getService(UserRepository::class);
        $repository->upgradePassword($user->object(), $hasher->hashPassword($user->object(), $newPassword));

        self::assertTrue($hasher->isPasswordValid($user->object(), $newPassword));
    }
}
