<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Repository;

use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\IntensityIndicator;
use App\CarbonFootprint\Domain\Model\SectorBenchmark\SectorBenchmark;
use App\CarbonFootprint\Domain\Model\SectorBenchmark\SectorBenchmarkRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.sector_benchmark', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineSectorBenchmarkRepository implements SectorBenchmarkRepository
{
    public function __construct(private EntityManagerInterface $manager) {}

    public function add(SectorBenchmark $benchmark): void
    {
        $this->manager->persist($benchmark);
        $this->manager->flush();
    }

    public function removeAll(): void
    {
        $this->manager->createQueryBuilder()
            ->delete(SectorBenchmark::class, 'b')
            ->getQuery()
            ->execute()
        ;
    }

    /** @param list<SectorBenchmark> $benchmarks */
    public function replaceAll(array $benchmarks): void
    {
        $this->manager->wrapInTransaction(function () use ($benchmarks): void {
            $this->removeAll();

            foreach ($benchmarks as $benchmark) {
                $this->manager->persist($benchmark);
            }

            $this->manager->flush();
        });
    }

    public function findBySegment(CarbonFootprintReportSubjectType $subjectType, string $segmentKey, int $year, IntensityIndicator $indicator): ?SectorBenchmark
    {
        return $this->manager->getRepository(SectorBenchmark::class)->findOneBy([
            'subjectType' => $subjectType,
            'segmentKey' => $segmentKey,
            'year' => $year,
            'indicator' => $indicator,
        ]);
    }

    /** @return list<SectorBenchmark> */
    public function findAll(): array
    {
        return $this->manager->getRepository(SectorBenchmark::class)->findAll();
    }
}
