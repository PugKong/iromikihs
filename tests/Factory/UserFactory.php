<?php

declare(strict_types=1);

namespace App\Tests\Factory;

use App\Entity\User;
use App\Entity\UserSyncState;
use App\Service\Shikimori\TokenData;
use App\Service\Shikimori\TokenDataEncryptor;
use DateTimeImmutable;
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
    public const DEFAULT_ACCESS_TOKEN = 'default access token';

    private UserPasswordHasherInterface $hasher;
    private TokenDataEncryptor $tokenEncryptor;

    public function __construct(UserPasswordHasherInterface $hasher, TokenDataEncryptor $tokenEncryptor)
    {
        $this->hasher = $hasher;
        $this->tokenEncryptor = $tokenEncryptor;

        parent::__construct();
    }

    public function withLinkedAccount(
        int $accountId = 6610,
        string $accessToken = null,
        string $refreshToken = null,
        DateTimeImmutable $expiresAt = null,
    ): self {
        return $this->afterInstantiate(
            function (User $user) use ($accountId, $accessToken, $refreshToken, $expiresAt): void {
                $sync = $user->getSync();
                $sync->setAccountId($accountId);

                $expiresAt ??= new DateTimeImmutable('+1 year');

                $token = $this->tokenEncryptor->encrypt(new TokenData(
                    accessToken: $accessToken ?? self::DEFAULT_ACCESS_TOKEN,
                    refreshToken: $refreshToken ?? 'default refresh token',
                    expiresAt: $expiresAt->getTimestamp(),
                ));
                $sync->setToken($token);
            },
        );
    }

    public function withSyncStatus(UserSyncState $state = null, DateTimeImmutable $syncedAt = null): self
    {
        return $this->afterInstantiate(function (User $user) use ($state, $syncedAt): void {
            $sync = $user->getSync();

            $sync->setState($state);
            $sync->setSyncedAt($syncedAt);
        });
    }

    protected function getDefaults(): array
    {
        return [
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
