<?php

declare(strict_types=1);

namespace App\Service\User;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final readonly class Create
{
    private UserRepository $users;
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserRepository $users, UserPasswordHasherInterface $passwordHasher)
    {
        $this->users = $users;
        $this->passwordHasher = $passwordHasher;
    }

    public function __invoke(CreateData $data): void
    {
        $user = new User();
        $user->setUsername($data->username);

        $password = $this->passwordHasher->hashPassword($user, $data->password);
        $user->setPassword($password);

        $this->users->save($user);
    }
}
