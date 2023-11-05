<?php

declare(strict_types=1);

namespace App\Tests\Factory;

use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;

/**
 * @extends ModelFactory<User>
 *
 * @method        Proxy<User>   create(array|callable $attributes = [])
 * @method static Proxy<User>   createOne(array $attributes = [])
 * @method static Proxy<User>   find(object|array|mixed $criteria)
 * @method static Proxy<User>   findOrCreate(array $attributes)
 * @method static Proxy<User>   first(string $sortedField = 'id')
 * @method static Proxy<User>   last(string $sortedField = 'id')
 * @method static Proxy<User>   random(array $attributes = [])
 * @method static Proxy<User>   randomOrCreate(array $attributes = [])
 * @method static Proxy<User>[] all()
 * @method static Proxy<User>[] createMany(int $number, array|callable $attributes = [])
 * @method static Proxy<User>[] createSequence(iterable|callable $sequence)
 * @method static Proxy<User>[] findBy(array $attributes)
 * @method static Proxy<User>[] randomRange(int $min, int $max, array $attributes = [])
 * @method static Proxy<User>[] randomSet(int $number, array $attributes = [])
 */
final class UserFactory extends ModelFactory
{
    private UserPasswordHasherInterface $hasher;

    public function __construct(UserPasswordHasherInterface $hasher)
    {
        $this->hasher = $hasher;

        parent::__construct();
    }

    public function withLinkedAccount(int $accountId = 6610): self
    {
        return $this->addState([
            'accountId' => $accountId,
            'token' => TokenFactory::new(),
        ]);
    }

    protected function getDefaults(): array
    {
        return [
            'token' => null,
            'username' => self::faker()->userName(),
            'password' => 'qwerty',
        ];
    }

    protected function initialize(): self
    {
        return $this
            ->afterInstantiate(function (User $user): void {
                $password = $this->hasher->hashPassword($user, $user->getPassword());
                $user->setPassword($password);
            })
        ;
    }

    protected static function getClass(): string
    {
        return User::class;
    }
}
