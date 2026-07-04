<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Repository;

use App\CarbonFootprint\Domain\Model\Activity\Activity;
use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Activity\ActivityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.activity', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineActivityRepository implements ActivityRepository
{
    public function __construct(private EntityManagerInterface $manager) {}

    public function save(Activity $activity): void
    {
        $this->manager->persist($activity);
        $this->manager->flush();
    }

    public function findById(ActivityId $id): ?Activity
    {
        return $this->manager->find(Activity::class, $id);
    }

    /**
     * @param ActivityId[] $ids
     *
     * @return Activity[]
     */
    public function findByIds(array $ids): array
    {
        if ([] === $ids) {
            return [];
        }

        $qb = $this->manager->createQueryBuilder();

        return $qb
            ->select('a')
            ->from(Activity::class, 'a')
            ->where($qb->expr()->in('a.id', ':ids'))
            ->setParameter('ids', array_map(static fn(ActivityId $id): string => (string) $id, $ids))
            ->getQuery()
            ->getResult()
        ;
    }

    public function remove(Activity $activity): void
    {
        $this->manager->remove($activity);
        $this->manager->flush();
    }
}
