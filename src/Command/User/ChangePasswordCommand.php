<?php

declare(strict_types=1);

namespace App\Command\User;

use App\Repository\UserRepository;
use App\Service\User\ChangePassword;
use App\Service\User\ChangePasswordData;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsCommand('app:user:change-password', description: 'Change user password.')]
final class ChangePasswordCommand extends Command
{
    use ValidationUtil;

    private UserRepository $users;
    private ValidatorInterface $validator;
    private ChangePassword $changePassword;

    public function __construct(UserRepository $users, ValidatorInterface $validator, ChangePassword $changePassword)
    {
        $this->users = $users;
        $this->validator = $validator;
        $this->changePassword = $changePassword;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('username', InputArgument::REQUIRED, 'The username');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        /** @var string $username */
        $username = $input->getArgument('username');
        $user = $this->users->findOneBy(['username' => $username]);
        if (null === $user) {
            $io->error(sprintf('User "%s" not found', $username));

            return self::FAILURE;
        }

        /** @var string|null $password */
        $password = $io->askHidden('New password');
        $password = (string) $password;
        /** @var string|null $passwordRepeat */
        $passwordRepeat = $io->askHidden('Repeat password');
        $passwordRepeat = (string) $passwordRepeat;
        if ($password !== $passwordRepeat) {
            $io->error('Passwords do not match');

            return self::FAILURE;
        }

        $data = new ChangePasswordData($user, $password);
        $violations = $this->validator->validate($data);
        if ($violations->count() > 0) {
            $this->printValidationErrors($io, $violations);

            return self::FAILURE;
        }
        ($this->changePassword)($data);

        $io->success('Password changed');

        return self::SUCCESS;
    }
}
