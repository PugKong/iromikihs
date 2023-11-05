<?php

declare(strict_types=1);

namespace App\Tests\Factory;

use App\Entity\Token;
use App\Service\Shikimori\TokenData;
use App\Service\Shikimori\TokenDataEncryptor;
use DateTimeImmutable;
use Zenstruck\Foundry\LazyValue;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;

/**
 * @extends ModelFactory<Token>
 *
 * @method        Proxy<Token>   create(array|callable $attributes = [])
 * @method static Proxy<Token>   createOne(array $attributes = [])
 * @method static Proxy<Token>   find(object|array|mixed $criteria)
 * @method static Proxy<Token>   findOrCreate(array $attributes)
 * @method static Proxy<Token>   first(string $sortedField = 'id')
 * @method static Proxy<Token>   last(string $sortedField = 'id')
 * @method static Proxy<Token>   random(array $attributes = [])
 * @method static Proxy<Token>   randomOrCreate(array $attributes = [])
 * @method static Token<Proxy>[] all()
 * @method static Token<Proxy>[] createMany(int $number, array|callable $attributes = [])
 * @method static Token<Proxy>[] createSequence(iterable|callable $sequence)
 * @method static Token<Proxy>[] findBy(array $attributes)
 * @method static Token<Proxy>[] randomRange(int $min, int $max, array $attributes = [])
 * @method static Token<Proxy>[] randomSet(int $number, array $attributes = [])
 */
final class TokenFactory extends ModelFactory
{
    public const DEFAULT_ACCESS_TOKEN = 'default access token';

    private TokenDataEncryptor $tokenEncryptor;

    public function __construct(TokenDataEncryptor $tokenEncryptor)
    {
        $this->tokenEncryptor = $tokenEncryptor;

        parent::__construct();
    }

    protected function getDefaults(): array
    {
        return [
            'user' => UserFactory::new(),
            'data' => LazyValue::new(fn () => $this->tokenEncryptor->encrypt(new TokenData(
                accessToken: self::DEFAULT_ACCESS_TOKEN,
                refreshToken: 'default refresh token',
                expiresAt: (new DateTimeImmutable('+1 year'))->getTimestamp(),
            ))),
        ];
    }

    protected static function getClass(): string
    {
        return Token::class;
    }
}
