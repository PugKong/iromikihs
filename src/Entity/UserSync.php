<?php

declare(strict_types=1);

namespace App\Entity;

use App\Exception\UserHasSyncInProgressException;
use App\Repository\UserSyncRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserSyncRepository::class)]
class UserSync
{
    #[ORM\Id]
    #[ORM\OneToOne(inversedBy: 'sync')]
    private User $user;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $token;

    #[ORM\Column(nullable: true)]
    private ?int $accountId;

    #[ORM\Column(nullable: true)]
    private ?UserSyncState $state;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $syncedAt;

    public function __construct(User $user)
    {
        $this->user = $user;
        $this->token = null;
        $this->accountId = null;
        $this->state = null;
        $this->syncedAt = null;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(?string $token): self
    {
        $this->token = $token;

        return $this;
    }

    public function getAccountId(): ?int
    {
        return $this->accountId;
    }

    public function setAccountId(?int $accountId): self
    {
        $this->accountId = $accountId;

        return $this;
    }

    public function getState(): ?UserSyncState
    {
        return $this->state;
    }

    public function setState(?UserSyncState $state): self
    {
        $this->state = $state;

        return $this;
    }

    public function getSyncedAt(): ?DateTimeImmutable
    {
        return $this->syncedAt;
    }

    public function setSyncedAt(?DateTimeImmutable $syncedAt): self
    {
        $this->syncedAt = $syncedAt;

        return $this;
    }

    /**
     * @phpstan-assert-if-true !null $this->getAccountId()
     * @phpstan-assert-if-true !null $this->getToken()
     */
    public function isLinked(): bool
    {
        return null !== $this->accountId && null !== $this->token;
    }

    public function isInProgress(): bool
    {
        return null !== $this->state && UserSyncState::FAILED !== $this->state;
    }

    /**
     * @throws UserHasSyncInProgressException
     */
    public function ensureNoActiveSync(): void
    {
        if ($this->isInProgress()) {
            throw UserHasSyncInProgressException::create($this->getUser());
        }
    }
}
