<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Repository;

use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\OrganizationalEmployeeParticipation\OrganizationalEmployeeParticipation;
use App\CarbonFootprint\Domain\Model\OrganizationalEmployeeParticipation\OrganizationalEmployeeParticipationId;
use App\CarbonFootprint\Domain\Model\OrganizationalEmployeeParticipation\OrganizationalEmployeeParticipationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.organizational_employee_participation', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineOrganizationalEmployeeParticipationRepository implements OrganizationalEmployeeParticipationRepository
{
    public function __construct(private readonly EntityManagerInterface $manager) {}

    public function add(OrganizationalEmployeeParticipation $participation): void
    {
        $this->manager->persist($participation);
        $this->manager->flush();
    }

    public function find(OrganizationalEmployeeParticipationId $participationId): ?OrganizationalEmployeeParticipation
    {
        $qb = $this->manager->createQueryBuilder();

        return $qb
            ->select('p')
            ->from(OrganizationalEmployeeParticipation::class, 'p')
            ->where($qb->expr()->eq('p.id', ':id'))
            ->setParameter('id', $participationId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByCarbonFootprint(CarbonFootprintReportSubjectType $subjectType, string $carbonFootprintId): ?OrganizationalEmployeeParticipation
    {
        $qb = $this->manager->createQueryBuilder();

        return $qb
            ->select('p')
            ->from(OrganizationalEmployeeParticipation::class, 'p')
            ->where($qb->expr()->eq('p.subjectType', ':subjectType'))
            ->andWhere($qb->expr()->eq('p.carbonFootprintId', ':carbonFootprintId'))
            ->setParameter('subjectType', $subjectType->value)
            ->setParameter('carbonFootprintId', $carbonFootprintId)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
