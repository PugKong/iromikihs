<?php

declare(strict_types=1);

namespace App\Service\Shikimori;

use App\Entity\Token;
use App\Entity\User;
use App\Repository\TokenRepository;
use App\Shikimori\Api\Auth\RefreshTokenRequest;
use App\Shikimori\Api\Auth\TokenResponse;
use App\Shikimori\Client\Shikimori;
use Psr\Clock\ClockInterface;
use RuntimeException;

final readonly class TokenStorage
{
    private ClockInterface $clock;
    private TokenRepository $tokens;
    private Shikimori $shikimori;
    private TokenDataEncryptor $tokenEncryptor;

    public function __construct(
        ClockInterface $clock,
        TokenRepository $tokens,
        Shikimori $shikimori,
        TokenDataEncryptor $tokenEncryptor,
    ) {
        $this->clock = $clock;
        $this->tokens = $tokens;
        $this->shikimori = $shikimori;
        $this->tokenEncryptor = $tokenEncryptor;
    }

    public function store(User $user, TokenResponse $data): void
    {
        $token = $this->tokens->find($user);
        if (null === $token) {
            $token = new Token();
            $token->setUser($user);
        }

        $this->storeResponse($token, $data);
    }

    public function retrieve(User $user): string
    {
        $token = $this->tokens->find($user->getId());
        if (null === $token) {
            throw new RuntimeException(sprintf('Oh no, user %s has no token', $user->getId()));
        }

        $data = $this->tokenEncryptor->decrypt($token->getData());
        if ($data->expiresAt < ($this->clock->now()->getTimestamp() + 60)) {
            $request = new RefreshTokenRequest($data->refreshToken);
            $response = $this->shikimori->request($request);

            $data = $this->storeResponse($token, $response);
        }

        return $data->accessToken;
    }

    private function storeResponse(Token $token, TokenResponse $response): TokenData
    {
        $data = new TokenData($response->accessToken, $response->refreshToken, $response->expiresAt());
        $ciphertext = $this->tokenEncryptor->encrypt($data);

        $token->setData($ciphertext);
        $this->tokens->save($token);

        return $data;
    }
}
