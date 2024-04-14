<?php

declare(strict_types=1);

namespace App\Tests\Command\User;

use App\Tests\Command\CommandTestCase;
use App\Tests\Factory\UserFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class CreateCommandTest extends CommandTestCase
{
    public function testCreateUser(): void
    {
        $tester = self::createCommandTester('app:user:create');
        $tester->setInputs([
            $username = 'johny doe',
            $password = 'th3 str0ng3st p4$$w0rd I h4v3 3v3r s33n',
            $password,
        ]);

        $tester->execute([]);

        $tester->assertCommandIsSuccessful();

        self::assertSame(
            [
                ...self::questionOutputStrings('Username'),
                ...self::questionOutputStrings('Password'),
                ...self::questionOutputStrings('Repeat password'),
                self::successOutputString('User "johny doe" created'),
            ],
            self::getCommandDisplayAsArray($tester),
        );

        $user = UserFactory::find(['username' => $username]);
        self::assertSame($username, $user->getUsername());
        $passwordHasher = self::getService(UserPasswordHasherInterface::class);
        self::assertTrue($passwordHasher->isPasswordValid($user->object(), $password));
    }

    /**
     * @dataProvider validationProvider
     */
    public function testValidation(array $inputs, array $display, ?callable $setUp = null): void
    {
        if (null !== $setUp) {
            $setUp();
        }

        $tester = self::createCommandTester('app:user:create');
        $tester->setInputs($inputs);

        $exitCode = $tester->execute([]);

        self::assertSame(Command::FAILURE, $exitCode);
        self::assertSame($display, self::getCommandDisplayAsArray($tester));
    }

    public static function validationProvider(): array
    {
        $shortUsernameOutput = [
            ...self::questionOutputStrings('Username'),
            self::errorOutputString('Username is too short. It should have 3 character(s) or more.'),
        ];

        return [
            'username should not be empty' => [[''], $shortUsernameOutput],
            'username should have at least 3 characters' => [['ab'], $shortUsernameOutput],
            'username should have at most 180 characters' => [
                [str_repeat('a', 181)],
                [
                    ...self::questionOutputStrings('Username'),
                    self::errorOutputString('Username is too long. It should have 180 character(s) or less.'),
                ],
            ],
            'username should be unique' => [
                ['john'],
                [
                    ...self::questionOutputStrings('Username'),
                    self::errorOutputString('Username "john" is already in use'),
                ],
                fn () => UserFactory::createOne(['username' => 'john']),
            ],
            'passwords should match' => [
                ['john', 'foo', 'bar'],
                [
                    ...self::questionOutputStrings('Username'),
                    ...self::questionOutputStrings('Password'),
                    ...self::questionOutputStrings('Repeat password'),
                    self::errorOutputString('Passwords do not match'),
                ],
            ],
            'password should be strong enough' => [
                ['john', 'qwerty', 'qwerty'],
                [
                    ...self::questionOutputStrings('Username'),
                    ...self::questionOutputStrings('Password'),
                    ...self::questionOutputStrings('Repeat password'),
                    self::errorOutputString('The password strength is too low. Please use a stronger password.'),
                ],
            ],
        ];
    }
}
