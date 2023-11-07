<?php

declare(strict_types=1);

namespace App\Service\Shikimori;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Shikimori\Api\Auth\RefreshTokenRequest;
use App\Shikimori\Api\Auth\TokenResponse;
use App\Shikimori\Client\Shikimori;
use Psr\Clock\ClockInterface;
use RuntimeException;

final readonly class TokenStorage
{
    private ClockInterface $clock;
    private UserRepository $users;
    private Shikimori $shikimori;
    private TokenDataEncryptor $tokenEncryptor;

    public function __construct(
        ClockInterface $clock,
        UserRepository $users,
        Shikimori $shikimori,
        TokenDataEncryptor $tokenEncryptor,
    ) {
        $this->clock = $clock;
        $this->users = $users;
        $this->shikimori = $shikimori;
        $this->tokenEncryptor = $tokenEncryptor;
    }

    public function store(User $user, TokenResponse $data): void
    {
        $this->storeResponse($user, $data);
    }

    public function retrieve(User $user): string
    {
        $token = $user->getToken();
        if (null === $token) {
            throw new RuntimeException(sprintf('Oh no, user %s has no token', $user->getId()));
        }

        $data = $this->tokenEncryptor->decrypt($token);
        if ($data->expiresAt < ($this->clock->now()->getTimestamp() + 60)) {
            $request = new RefreshTokenRequest($data->refreshToken);
            $response = $this->shikimori->request($request);

            $data = $this->storeResponse($user, $response);
        }

        return $data->accessToken;
    }

    private function storeResponse(User $user, TokenResponse $response): TokenData
    {
        $data = new TokenData($response->accessToken, $response->refreshToken, $response->expiresAt());
        $ciphertext = $this->tokenEncryptor->encrypt($data);

        $user->setToken($ciphertext);
        $this->users->save($user);

        return $data;
    }
}
