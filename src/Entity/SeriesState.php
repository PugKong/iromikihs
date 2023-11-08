<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\SeriesStateRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Uid\UuidV7;

#[ORM\Entity(repositoryClass: SeriesStateRepository::class)]
#[ORM\UniqueConstraint(columns: ['user_id', 'series_id'])]
class SeriesState
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME)]
    private UuidV7 $id;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private Series $series;

    #[ORM\Column]
    private UserSeriesState $state;

    public function __construct()
    {
        $this->id = Uuid::v7();
    }

    public function getId(): UuidV7
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getSeries(): Series
    {
        return $this->series;
    }

    public function setSeries(Series $series): self
    {
        $this->series = $series;

        return $this;
    }

    public function getState(): UserSeriesState
    {
        return $this->state;
    }

    public function setState(UserSeriesState $state): self
    {
        $this->state = $state;

        return $this;
    }
}
