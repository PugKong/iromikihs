<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Series;
use App\Entity\SeriesRate;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

use function count;

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

    /**
     * @param SeriesRate[] $rates
     *
     * @return SeriesRate[]
     */
    public function findOtherByUser(User $user, array $rates): array
    {
        $builder = $this
            ->createQueryBuilder('r')
            ->andWhere('r.user = :user')
            ->setParameter('user', $user)
        ;

        if (count($rates) > 0) {
            $builder->andWhere('r NOT IN (:rate)')->setParameter('rate', $rates);
        }

        /** @var SeriesRate[] $result */
        $result = $builder->getQuery()->getResult();

        return $result;
    }
}
