<?php

declare(strict_types=1);

namespace App\Service\Shikimori;

use App\Entity\User;
use App\Shikimori\Api\Auth\RefreshTokenRequest;
use App\Shikimori\Api\Auth\TokenResponse;
use App\Shikimori\Client\Shikimori;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;
use RuntimeException;

final readonly class TokenStorage
{
    private ClockInterface $clock;
    private Shikimori $shikimori;
    private TokenDataEncryptor $tokenEncryptor;
    private EntityManagerInterface $entityManager;

    public function __construct(
        ClockInterface $clock,
        Shikimori $shikimori,
        TokenDataEncryptor $tokenEncryptor,
        EntityManagerInterface $entityManager,
    ) {
        $this->clock = $clock;
        $this->shikimori = $shikimori;
        $this->tokenEncryptor = $tokenEncryptor;
        $this->entityManager = $entityManager;
    }

    public function store(User $user, TokenResponse $data): void
    {
        $this->storeResponse($user, $data);
    }

    public function retrieve(User $user): string
    {
        $token = $user->getSync()->getToken();
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

        $sync = $user->getSync();
        $sync->setToken($ciphertext);
        $this->entityManager->persist($sync);
        $this->entityManager->flush();

        return $data;
    }
}
