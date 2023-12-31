<?php

declare(strict_types=1);

namespace App\Twig\Component;

use App\Entity\User;
use App\Entity\UserSync;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class SyncStatus
{
    private User $user;
    private UserSync $sync;

    public function mount(User $user): void
    {
        $this->user = $user;
        $this->sync = $user->getSync();
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getSync(): UserSync
    {
        return $this->sync;
    }

    public function getState(): ?string
    {
        return $this->sync->getState()?->value;
    }
}
