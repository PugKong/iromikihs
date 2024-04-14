<?php

declare(strict_types=1);

namespace App\Tests\Command\User;

use App\Tests\Command\CommandTestCase;
use App\Tests\Factory\UserFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class ChangePasswordCommandTest extends CommandTestCase
{
    public function testChangePassword(): void
    {
        $user = UserFactory::createOne(['username' => $username = 'john', 'password' => 'qwerty']);

        $tester = self::createCommandTester('app:user:change-password');
        $tester->setInputs([
            $password = 'th3 str0ng3st p4$$w0rd I h4v3 3v3r s33n',
            $password,
        ]);
        $tester->execute(['username' => $username]);
        $tester->assertCommandIsSuccessful();

        self::assertSame(
            [
                ...self::questionOutputStrings('New password'),
                ...self::questionOutputStrings('Repeat password'),
                self::successOutputString('Password changed'),
            ],
            self::getCommandDisplayAsArray($tester),
        );

        $hasher = self::getService(UserPasswordHasherInterface::class);
        self::assertTrue($hasher->isPasswordValid($user->object(), $password));
    }

    /**
     * @dataProvider validationProvider
     */
    public function testValidation(array $inputs, string $username, array $display, ?callable $setUp = null): void
    {
        if (null !== $setUp) {
            $setUp();
        }

        $tester = self::createCommandTester('app:user:change-password');
        $tester->setInputs($inputs);
        $exitCode = $tester->execute(['username' => $username]);

        self::assertSame(Command::FAILURE, $exitCode);
        self::assertSame($display, self::getCommandDisplayAsArray($tester));
    }

    public static function validationProvider(): array
    {
        $username = 'john';
        $createUser = fn () => UserFactory::createOne(['username' => $username]);
        $passwordQuestions = [
            ...self::questionOutputStrings('New password'),
            ...self::questionOutputStrings('Repeat password'),
        ];

        return [
            'user not exist' => [[], 'john', [self::errorOutputString('User "john" not found')]],
            'password mismatch' => [
                ['qwerty', 'asdf'],
                $username,
                [...$passwordQuestions, self::errorOutputString('Passwords do not match')],
                $createUser,
            ],
            'empty password' => [
                ['', ''],
                $username,
                [
                    ...$passwordQuestions,
                    self::errorOutputString('The password strength is too low. Please use a stronger password.'),
                ],
                $createUser,
            ],
            'weak password' => [
                ['qwerty', 'qwerty'],
                $username,
                [
                    ...$passwordQuestions,
                    self::errorOutputString('The password strength is too low. Please use a stronger password.'),
                ],
                $createUser,
            ],
        ];
    }
}
