<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Series;
use App\Entity\SeriesRate;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SeriesRate>
 */
class SeriesRateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SeriesRate::class);
    }

    public function findOrNew(User $user, Series $series): SeriesRate
    {
        $rate = $this->findOneBy(['user' => $user, 'series' => $series]);
        if (null === $rate) {
            $rate = new SeriesRate();
            $rate->setUser($user);
            $rate->setSeries($series);
        }

        return $rate;
    }

    public function save(SeriesRate ...$series): void
    {
        foreach ($series as $userSeries) {
            $this->getEntityManager()->persist($userSeries);
        }
        $this->getEntityManager()->flush();
    }
}
