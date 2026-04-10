<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Persistence\Doctrine\Repository;

use App\FoodLoss\Domain\Model\TrainingSession\TrainingSession;
use App\FoodLoss\Domain\Model\TrainingSession\TrainingSessionFilter;
use App\FoodLoss\Domain\Model\TrainingSession\TrainingSessionId;
use App\FoodLoss\Domain\Model\TrainingSession\TrainingSessionRepository;
use App\Security\Domain\Model\Account\AccountId;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.training_session', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineTrainingSessionRepository implements TrainingSessionRepository
{
    public function __construct(private readonly EntityManagerInterface $manager) {}

    public function add(TrainingSession $trainingSession): void
    {
        $this->manager->persist($trainingSession);
        $this->manager->flush();
    }

    public function remove(TrainingSession $trainingSession): void
    {
        $this->manager->remove($trainingSession);
        $this->manager->flush();
    }

    public function find(TrainingSessionId $trainingSessionId): ?TrainingSession
    {
        $qb = $this->createQueryBuilderFromFilter(new TrainingSessionFilter());

        return $qb
            ->andWhere(
                $qb->expr()->eq('e.id', ':id'),
            )
            ->setParameter('id', (string) $trainingSessionId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    private function createQueryBuilderFromFilter(TrainingSessionFilter $filter): QueryBuilder
    {
        $qb = $this->manager->createQueryBuilder();

        $qb->select('e')
            ->from(TrainingSession::class, 'e')
            ->where(
                $qb->expr()->orX(
                    $qb->expr()->isNull('e.removed'),
                    $qb->expr()->eq('e.removed', ':removed'),
                ),
            )
            ->setParameter('removed', false, Types::BOOLEAN)
        ;

        if (!empty($filter->title)) {
            $qb
                ->andWhere(
                    $qb->expr()->like('e.title', ':title'),
                )
                ->setParameter('title', "%{$filter->title}%")
            ;
        }

        if ($filter->accountId instanceof AccountId) {
            $qb
                ->andWhere(
                    $qb->expr()->eq('e.accountId', ':accountId'),
                )
                ->setParameter('accountId', (string) $filter->accountId)
            ;
        }

        $way = 'ASC' === $filter->orderWay ? 'ASC' : 'DESC';

        match ($filter->orderBy) {
            'sessionDate' => $qb->orderBy('e.sessionDate', $way),
            'registeredAt' => $qb->orderBy('e.registeredAt', $way),
            default => $qb->orderBy('e.sessionDate', 'DESC'),
        };

        return $qb;
    }

    /** @return array<TrainingSession> */
    public function findByFilter(TrainingSessionFilter $filter): array
    {
        $qb = $this->createQueryBuilderFromFilter($filter);

        $query = $qb->getQuery();

        if ($filter->limit > 0) {
            $query
                ->setMaxResults($filter->limit)
                ->setFirstResult($filter->page * $filter->limit)
            ;
        }

        return (array) $query->getResult();
    }

    public function totalResultsByFilter(TrainingSessionFilter $filter): int
    {
        $qb = $this->createQueryBuilderFromFilter($filter);

        return (int) $qb->select('COUNT(DISTINCT e)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }
}
