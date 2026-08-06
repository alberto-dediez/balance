<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\TripCalculation;

use App\CarbonFootprint\Domain\Model\TripCalculation\TripCalculationFilter;
use App\CarbonFootprint\Domain\Model\TripCalculation\TripCalculationRepository;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkId;
use PHPUnit\Framework\TestCase;

abstract class TripCalculationRepositoryTest extends TestCase
{
    public function testAddTripCalculationToRepository(): void
    {
        $repository = $this->createSUT();

        $tripCalculation = TripCalculationMother::random();

        $this->assertNull($repository->find($tripCalculation->id()));

        $repository->add($tripCalculation);

        $recovered = $repository->find($tripCalculation->id());

        $this->assertNotNull($recovered);
        $this->assertEquals($tripCalculation->id(), $recovered->id());
    }

    public function testFindByLinkId(): void
    {
        $repository = $this->createSUT();

        $linkId = (string) TripCalculationLinkId::generate();

        $repository->add(TripCalculationMother::fromLinkId($linkId));
        $repository->add(TripCalculationMother::fromLinkId($linkId));
        $repository->add(TripCalculationMother::random());

        $this->assertCount(2, $repository->findByLinkId(new TripCalculationLinkId($linkId)));
    }

    public function testFindByFilterRestrictsToThePeriod(): void
    {
        $repository = $this->createSUT();

        $linkId = (string) TripCalculationLinkId::generate();

        $repository->add(TripCalculationMother::registeredAt($linkId, '2026-01-15 10:00:00'));
        $repository->add(TripCalculationMother::registeredAt($linkId, '2026-02-15 10:00:00'));
        $repository->add(TripCalculationMother::registeredAt($linkId, '2026-03-15 10:00:00'));

        $filter = new TripCalculationFilter(
            tripCalculationLinkId: new TripCalculationLinkId($linkId),
            from: new \DateTimeImmutable('2026-02-01 00:00:00'),
            to: new \DateTimeImmutable('2026-02-28 23:59:59'),
            limit: 0,
        );

        $this->assertCount(1, $repository->findByFilter($filter));
        $this->assertSame(1, $repository->totalResultsByFilter($filter));
    }

    public function testFindByFilterWithoutPeriodReturnsThemAll(): void
    {
        $repository = $this->createSUT();

        $linkId = (string) TripCalculationLinkId::generate();

        $repository->add(TripCalculationMother::registeredAt($linkId, '2026-01-15 10:00:00'));
        $repository->add(TripCalculationMother::registeredAt($linkId, '2026-03-15 10:00:00'));

        $filter = new TripCalculationFilter(tripCalculationLinkId: new TripCalculationLinkId($linkId), limit: 0);

        $this->assertCount(2, $repository->findByFilter($filter));
    }

    abstract protected function createSUT(): TripCalculationRepository;
}
