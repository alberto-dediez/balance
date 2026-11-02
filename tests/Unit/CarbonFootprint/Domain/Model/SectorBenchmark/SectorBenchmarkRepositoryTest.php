<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\SectorBenchmark;

use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\IntensityIndicator;
use App\CarbonFootprint\Domain\Model\SectorBenchmark\SectorBenchmarkRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class SectorBenchmarkRepositoryTest extends KernelTestCase
{
    public function testFindBySegment(): void
    {
        $repository = $this->createSUT();
        $hotel = SectorBenchmarkMother::computed('Hotel', 2025, IntensityIndicator::PerStay, 3.0);
        $repository->add($hotel);
        $repository->add(SectorBenchmarkMother::computed('Hotel', 2024, IntensityIndicator::PerStay, 4.0));
        $repository->add(SectorBenchmarkMother::computed('Hotel', 2025, IntensityIndicator::PerSquareMeter, 90.0));
        $repository->add(SectorBenchmarkMother::computed('company', 2025, IntensityIndicator::PerStay, 5.0, CarbonFootprintReportSubjectType::Company));

        $found = $repository->findBySegment(CarbonFootprintReportSubjectType::Property, 'Hotel', 2025, IntensityIndicator::PerStay);

        $this->assertEquals($hotel->id(), $found?->id());
        $this->assertSame('3.0000', $found?->median());
        $this->assertNull($repository->findBySegment(CarbonFootprintReportSubjectType::Property, 'Camping', 2025, IntensityIndicator::PerStay));
        $this->assertCount(4, $repository->findAll());
    }

    public function testRemoveAll(): void
    {
        $repository = $this->createSUT();
        $repository->add(SectorBenchmarkMother::computed());
        $repository->add(SectorBenchmarkMother::computed('Camping'));

        $repository->removeAll();

        $this->assertSame([], $repository->findAll());
    }

    public function testReplaceAll(): void
    {
        $repository = $this->createSUT();
        $repository->add(SectorBenchmarkMother::computed('Camping'));
        $hotel = SectorBenchmarkMother::computed('Hotel');
        $company = SectorBenchmarkMother::computed('company', subjectType: CarbonFootprintReportSubjectType::Company);

        $repository->replaceAll([$hotel, $company]);

        $this->assertCount(2, $repository->findAll());
        $this->assertNull($repository->findBySegment(CarbonFootprintReportSubjectType::Property, 'Camping', 2025, IntensityIndicator::PerStay));
        $this->assertEquals($hotel->id(), $repository->findBySegment(CarbonFootprintReportSubjectType::Property, 'Hotel', 2025, IntensityIndicator::PerStay)?->id());
        $this->assertEquals($company->id(), $repository->findBySegment(CarbonFootprintReportSubjectType::Company, 'company', 2025, IntensityIndicator::PerStay)?->id());
    }

    abstract protected function createSUT(): SectorBenchmarkRepository;
}
