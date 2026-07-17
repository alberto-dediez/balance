<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Repository;

use App\CarbonFootprint\Domain\Model\EmployeeParticipation\EmployeeParticipation;
use App\CarbonFootprint\Domain\Model\EmployeeParticipation\EmployeeParticipationId;
use App\CarbonFootprint\Domain\Model\EmployeeParticipation\EmployeeParticipationRepository;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintId;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.employee_participation', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineEmployeeParticipationRepository implements EmployeeParticipationRepository
{
    public function __construct(private readonly EntityManagerInterface $manager) {}

    public function add(EmployeeParticipation $employeeParticipation): void
    {
        $this->manager->persist($employeeParticipation);
        $this->manager->flush();
    }

    public function find(EmployeeParticipationId $employeeParticipationId): ?EmployeeParticipation
    {
        $qb = $this->manager->createQueryBuilder();

        return $qb
            ->select('p')
            ->from(EmployeeParticipation::class, 'p')
            ->where($qb->expr()->eq('p.id', ':id'))
            ->setParameter('id', $employeeParticipationId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByEventCarbonFootprintId(EventCarbonFootprintId $eventCarbonFootprintId): ?EmployeeParticipation
    {
        $qb = $this->manager->createQueryBuilder();

        return $qb
            ->select('p')
            ->from(EmployeeParticipation::class, 'p')
            ->where($qb->expr()->eq('p.eventCarbonFootprintId', ':eventCarbonFootprintId'))
            ->setParameter('eventCarbonFootprintId', (string) $eventCarbonFootprintId)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
