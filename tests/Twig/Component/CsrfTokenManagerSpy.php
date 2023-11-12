<?php

declare(strict_types=1);

namespace App\Tests\Twig\Component;

use PHPUnit\Framework\Assert;
use RuntimeException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

use function array_key_exists;

final class CsrfTokenManagerSpy implements CsrfTokenManagerInterface
{
    /**
     * @var array<string, string>
     */
    private array $tokens;

    /**
     * @var mixed[]
     */
    private array $calls = [];

    public function __construct(array $tokens)
    {
        $this->tokens = $tokens;
    }

    public function register(ContainerInterface $container): void
    {
        $container->set(CsrfTokenManagerInterface::class, $this);
    }

    public function getToken(string $tokenId): CsrfToken
    {
        $this->calls[] = ['getToken', $tokenId];

        if (!array_key_exists($tokenId, $this->tokens)) {
            throw new RuntimeException("Oh no, no token with $tokenId id");
        }

        return new CsrfToken($tokenId, $this->tokens[$tokenId]);
    }

    public function refreshToken(string $tokenId): CsrfToken
    {
        $this->calls[] = ['refreshToken', $tokenId];

        if (!array_key_exists($tokenId, $this->tokens)) {
            throw new RuntimeException("Oh no, no token with $tokenId id");
        }
        $this->tokens[$tokenId] = strrev($this->tokens[$tokenId]);

        return new CsrfToken($tokenId, $this->tokens[$tokenId]);
    }

    public function removeToken(string $tokenId): ?string
    {
        $this->calls[] = ['removeToken', $tokenId];

        $value = $this->tokens[$tokenId] ?? null;
        unset($this->tokens[$tokenId]);

        return $value;
    }

    public function isTokenValid(CsrfToken $token): bool
    {
        $this->calls[] = ['isTokenValid', $token];

        $storedToken = $this->tokens[$token->getId()] ?? null;
        if (null === $storedToken) {
            return false;
        }

        return $storedToken === $token->getValue();
    }

    public function assertCalls(int $expected): void
    {
        Assert::assertCount($expected, $this->calls);
    }

    public function assertHasCall(string $methodName, mixed ...$args): void
    {
        Assert::assertContainsEquals([$methodName, ...$args], $this->calls);
    }
}
