<?php

declare(strict_types=1);

namespace App\Command\User;

use App\Service\User\Create;
use App\Service\User\CreateData;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsCommand('app:user:create', description: 'Creates a new user.')]
final class CreateCommand extends Command
{
    use ValidationUtil;

    private Create $create;
    private ValidatorInterface $validator;

    public function __construct(Create $create, ValidatorInterface $validator)
    {
        $this->create = $create;
        $this->validator = $validator;

        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $data = new CreateData();

        /** @var string|null $username */
        $username = $io->ask('Username');
        $data->username = (string) $username;
        $violations = $this->validator->validate($data, groups: [CreateData::USERNAME_GROUP]);
        if ($violations->count() > 0) {
            $this->printValidationErrors($io, $violations);

            return self::FAILURE;
        }

        /** @var string|null $password */
        $password = $io->askHidden('Password');
        $password = (string) $password;
        /** @var string|null $passwordRepeat */
        $passwordRepeat = $io->askHidden('Repeat password');
        if ($password !== $passwordRepeat) {
            $io->error('Passwords do not match');

            return self::FAILURE;
        }

        $data->password = $password;
        $violations = $this->validator->validate($data, groups: [CreateData::PASSWORD_GROUP]);
        if ($violations->count() > 0) {
            $this->printValidationErrors($io, $violations);

            return self::FAILURE;
        }

        ($this->create)($data);

        $io->success(sprintf('User "%s" created', $data->username));

        return self::SUCCESS;
    }
}
