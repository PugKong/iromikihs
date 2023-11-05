<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\TokenRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TokenRepository::class)]
class Token
{
    #[ORM\Id]
    #[ORM\OneToOne(inversedBy: 'token')]
    private User $user;

    #[ORM\Column(type: Types::TEXT)]
    private string $data;

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): void
    {
        if (!$user->hasToken() || $user->getToken() !== $this) {
            $user->setToken($this);
        }
        $this->user = $user;
    }

    public function hasUser(): bool
    {
        return false !== ($this->user ?? false);
    }

    public function getData(): string
    {
        return $this->data;
    }

    public function setData(string $data): self
    {
        $this->data = $data;

        return $this;
    }
}
