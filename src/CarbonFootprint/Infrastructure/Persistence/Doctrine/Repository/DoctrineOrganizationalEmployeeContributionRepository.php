<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Repository;

use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution\OrganizationalEmployeeContribution;
use App\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution\OrganizationalEmployeeContributionId;
use App\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution\OrganizationalEmployeeContributionRepository;
use App\HumanResources\Domain\Model\Employee\EmployeeId;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.organizational_employee_contribution', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineOrganizationalEmployeeContributionRepository implements OrganizationalEmployeeContributionRepository
{
    public function __construct(private readonly EntityManagerInterface $manager) {}

    public function add(OrganizationalEmployeeContribution $contribution): void
    {
        $this->manager->persist($contribution);
        $this->manager->flush();
    }

    public function find(OrganizationalEmployeeContributionId $contributionId): ?OrganizationalEmployeeContribution
    {
        $qb = $this->manager->createQueryBuilder();

        return $qb
            ->select('c')
            ->from(OrganizationalEmployeeContribution::class, 'c')
            ->where($qb->expr()->eq('c.id', ':id'))
            ->setParameter('id', $contributionId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByToken(#[\SensitiveParameter] string $token): ?OrganizationalEmployeeContribution
    {
        $qb = $this->manager->createQueryBuilder();

        return $qb
            ->select('c')
            ->from(OrganizationalEmployeeContribution::class, 'c')
            ->where($qb->expr()->eq('c.token', ':token'))
            ->setParameter('token', $token)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /** @return list<OrganizationalEmployeeContribution> */
    public function findByCarbonFootprint(CarbonFootprintReportSubjectType $subjectType, string $carbonFootprintId): array
    {
        $qb = $this->manager->createQueryBuilder();

        return (array) $qb
            ->select('c')
            ->from(OrganizationalEmployeeContribution::class, 'c')
            ->where($qb->expr()->eq('c.subjectType', ':subjectType'))
            ->andWhere($qb->expr()->eq('c.carbonFootprintId', ':carbonFootprintId'))
            ->setParameter('subjectType', $subjectType->value)
            ->setParameter('carbonFootprintId', $carbonFootprintId)
            ->orderBy('c.registeredAt', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findByCarbonFootprintAndEmployee(CarbonFootprintReportSubjectType $subjectType, string $carbonFootprintId, EmployeeId $employeeId): ?OrganizationalEmployeeContribution
    {
        $qb = $this->manager->createQueryBuilder();

        return $qb
            ->select('c')
            ->from(OrganizationalEmployeeContribution::class, 'c')
            ->where($qb->expr()->eq('c.subjectType', ':subjectType'))
            ->andWhere($qb->expr()->eq('c.carbonFootprintId', ':carbonFootprintId'))
            ->andWhere($qb->expr()->eq('c.employeeId', ':employeeId'))
            ->setParameter('subjectType', $subjectType->value)
            ->setParameter('carbonFootprintId', $carbonFootprintId)
            ->setParameter('employeeId', (string) $employeeId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}
