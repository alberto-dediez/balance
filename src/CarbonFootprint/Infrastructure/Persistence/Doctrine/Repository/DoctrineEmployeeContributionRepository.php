<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Repository;

use App\CarbonFootprint\Domain\Model\EmployeeContribution\EmployeeContribution;
use App\CarbonFootprint\Domain\Model\EmployeeContribution\EmployeeContributionId;
use App\CarbonFootprint\Domain\Model\EmployeeContribution\EmployeeContributionRepository;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintId;
use App\HumanResources\Domain\Model\Employee\EmployeeId;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.employee_contribution', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineEmployeeContributionRepository implements EmployeeContributionRepository
{
    public function __construct(private readonly EntityManagerInterface $manager) {}

    public function add(EmployeeContribution $employeeContribution): void
    {
        $this->manager->persist($employeeContribution);
        $this->manager->flush();
    }

    public function find(EmployeeContributionId $employeeContributionId): ?EmployeeContribution
    {
        $qb = $this->manager->createQueryBuilder();

        return $qb
            ->select('c')
            ->from(EmployeeContribution::class, 'c')
            ->where($qb->expr()->eq('c.id', ':id'))
            ->setParameter('id', $employeeContributionId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByToken(#[\SensitiveParameter] string $token): ?EmployeeContribution
    {
        $qb = $this->manager->createQueryBuilder();

        return $qb
            ->select('c')
            ->from(EmployeeContribution::class, 'c')
            ->where($qb->expr()->eq('c.token', ':token'))
            ->setParameter('token', $token)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /** @return list<EmployeeContribution> */
    public function findByEventCarbonFootprintId(EventCarbonFootprintId $eventCarbonFootprintId): array
    {
        $qb = $this->manager->createQueryBuilder();

        return (array) $qb
            ->select('c')
            ->from(EmployeeContribution::class, 'c')
            ->where($qb->expr()->eq('c.eventCarbonFootprintId', ':eventCarbonFootprintId'))
            ->setParameter('eventCarbonFootprintId', (string) $eventCarbonFootprintId)
            ->orderBy('c.registeredAt', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findByEventCarbonFootprintIdAndEmployeeId(EventCarbonFootprintId $eventCarbonFootprintId, EmployeeId $employeeId): ?EmployeeContribution
    {
        $qb = $this->manager->createQueryBuilder();

        return $qb
            ->select('c')
            ->from(EmployeeContribution::class, 'c')
            ->where($qb->expr()->eq('c.eventCarbonFootprintId', ':eventCarbonFootprintId'))
            ->andWhere($qb->expr()->eq('c.employeeId', ':employeeId'))
            ->setParameter('eventCarbonFootprintId', (string) $eventCarbonFootprintId)
            ->setParameter('employeeId', (string) $employeeId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}
