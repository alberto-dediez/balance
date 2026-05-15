<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Persistence\Doctrine\Repository;

use App\FoodLoss\Domain\Model\TrainingAttendance\TrainingAttendance;
use App\FoodLoss\Domain\Model\TrainingAttendance\TrainingAttendanceFilter;
use App\FoodLoss\Domain\Model\TrainingAttendance\TrainingAttendanceId;
use App\FoodLoss\Domain\Model\TrainingAttendance\TrainingAttendanceRepository;
use App\FoodLoss\Domain\Model\TrainingSession\TrainingSessionId;
use App\HumanResources\Domain\Model\Employee\EmployeeId;
use App\Security\Domain\Model\Account\AccountId;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.training_attendance', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineTrainingAttendanceRepository implements TrainingAttendanceRepository
{
    public function __construct(private readonly EntityManagerInterface $manager) {}

    public function add(TrainingAttendance $trainingAttendance): void
    {
        $this->manager->persist($trainingAttendance);
        $this->manager->flush();
    }

    public function find(TrainingAttendanceId $trainingAttendanceId): ?TrainingAttendance
    {
        $qb = $this->createQueryBuilderFromFilter(new TrainingAttendanceFilter());

        return $qb
            ->andWhere($qb->expr()->eq('e.id', ':id'))
            ->setParameter('id', (string) $trainingAttendanceId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /** @return array<TrainingAttendance> */
    public function findByFilter(TrainingAttendanceFilter $filter): array
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

    public function totalResultsByFilter(TrainingAttendanceFilter $filter): int
    {
        $qb = $this->createQueryBuilderFromFilter($filter);

        return (int) $qb->select('COUNT(DISTINCT e)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    public function countByFilePath(string $filePath): int
    {
        $qb = $this->manager->createQueryBuilder();

        return (int) $qb->select('COUNT(e)')
            ->from(TrainingAttendance::class, 'e')
            ->where(
                $qb->expr()->orX(
                    $qb->expr()->isNull('e.removed'),
                    $qb->expr()->eq('e.removed', ':removed'),
                ),
            )
            ->andWhere($qb->expr()->eq('e.filePath', ':filePath'))
            ->setParameter('removed', false, Types::BOOLEAN)
            ->setParameter('filePath', $filePath)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    private function createQueryBuilderFromFilter(TrainingAttendanceFilter $filter): QueryBuilder
    {
        $qb = $this->manager->createQueryBuilder();

        $qb->select('e')
            ->from(TrainingAttendance::class, 'e')
            ->where(
                $qb->expr()->orX(
                    $qb->expr()->isNull('e.removed'),
                    $qb->expr()->eq('e.removed', ':removed'),
                ),
            )
            ->setParameter('removed', false, Types::BOOLEAN)
        ;

        if ($filter->trainingSessionId instanceof TrainingSessionId) {
            $qb
                ->andWhere($qb->expr()->eq('e.trainingSessionId', ':trainingSessionId'))
                ->setParameter('trainingSessionId', (string) $filter->trainingSessionId)
            ;
        }

        if ($filter->employeeId instanceof EmployeeId) {
            $qb
                ->andWhere($qb->expr()->eq('e.employeeId', ':employeeId'))
                ->setParameter('employeeId', (string) $filter->employeeId)
            ;
        }

        if ($filter->accountId instanceof AccountId) {
            $qb
                ->andWhere($qb->expr()->eq('e.accountId', ':accountId'))
                ->setParameter('accountId', (string) $filter->accountId)
            ;
        }

        if (null !== $filter->filePath) {
            $qb
                ->andWhere($qb->expr()->eq('e.filePath', ':filePath'))
                ->setParameter('filePath', $filter->filePath)
            ;
        }

        $way = 'ASC' === $filter->orderWay ? 'ASC' : 'DESC';

        match ($filter->orderBy) {
            'attendedAt' => $qb->orderBy('e.attendedAt', $way),
            default => $qb->orderBy('e.registeredAt', $way),
        };

        return $qb;
    }
}
