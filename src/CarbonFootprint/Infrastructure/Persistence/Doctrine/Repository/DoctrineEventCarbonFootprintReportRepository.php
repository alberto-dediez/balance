<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Repository;

use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReport;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReportId;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReportRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.event_carbon_footprint_report', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineEventCarbonFootprintReportRepository implements EventCarbonFootprintReportRepository
{
    public function __construct(private readonly EntityManagerInterface $manager) {}

    public function add(EventCarbonFootprintReport $report): void
    {
        $this->manager->persist($report);
        $this->manager->flush();
    }

    public function remove(EventCarbonFootprintReport $report): void
    {
        $this->manager->remove($report);
        $this->manager->flush();
    }

    public function find(EventCarbonFootprintReportId $id): ?EventCarbonFootprintReport
    {
        return $this->manager->find(EventCarbonFootprintReport::class, $id);
    }

    public function findByEventCarbonFootprintId(EventCarbonFootprintId $eventCarbonFootprintId): ?EventCarbonFootprintReport
    {
        $qb = $this->manager->createQueryBuilder();

        return $qb
            ->select('r')
            ->from(EventCarbonFootprintReport::class, 'r')
            ->where($qb->expr()->eq('r.eventCarbonFootprintId', ':eventCarbonFootprintId'))
            ->setParameter('eventCarbonFootprintId', (string) $eventCarbonFootprintId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}
