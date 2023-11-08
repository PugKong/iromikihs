<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Series;
use App\Entity\SeriesState;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SeriesState>
 */
class SeriesStateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SeriesState::class);
    }

    public function findOrNew(User $user, Series $series): SeriesState
    {
        $userSeries = $this->findOneBy(['user' => $user, 'series' => $series]);
        if (null === $userSeries) {
            $userSeries = new SeriesState();
            $userSeries->setUser($user);
            $userSeries->setSeries($series);
        }

        return $userSeries;
    }

    public function save(SeriesState ...$series): void
    {
        foreach ($series as $userSeries) {
            $this->getEntityManager()->persist($userSeries);
        }
        $this->getEntityManager()->flush();
    }
}
