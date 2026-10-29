<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\OrganizationalEmployeeParticipation;

use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\OrganizationalEmployeeParticipation\OrganizationalEmployeeParticipationId;
use App\CarbonFootprint\Domain\Model\OrganizationalEmployeeParticipation\OrganizationalEmployeeParticipationRepository;
use PHPUnit\Framework\TestCase;

abstract class OrganizationalEmployeeParticipationRepositoryTest extends TestCase
{
    public function testAddParticipationToRepository(): void
    {
        $repository = $this->createSUT();

        $participation = OrganizationalEmployeeParticipationMother::registered();

        $this->assertNull($repository->find($participation->id()));

        $repository->add($participation);

        $recovered = $repository->find($participation->id());

        $this->assertNotNull($recovered);
        $this->assertEquals($participation->id(), $recovered->id());
    }

    public function testFindByCarbonFootprint(): void
    {
        $repository = $this->createSUT();

        $participation = OrganizationalEmployeeParticipationMother::from(CarbonFootprintReportSubjectType::Company, 'footprint-1', (string) OrganizationalEmployeeParticipationId::generate());
        $repository->add($participation);
        $repository->add(OrganizationalEmployeeParticipationMother::from(CarbonFootprintReportSubjectType::Property, 'footprint-2', (string) OrganizationalEmployeeParticipationId::generate()));

        $recovered = $repository->findByCarbonFootprint(CarbonFootprintReportSubjectType::Company, 'footprint-1');

        $this->assertNotNull($recovered);
        $this->assertEquals($participation->id(), $recovered->id());
        $this->assertNull($repository->findByCarbonFootprint(CarbonFootprintReportSubjectType::Property, 'footprint-1'));
    }

    abstract protected function createSUT(): OrganizationalEmployeeParticipationRepository;
}
