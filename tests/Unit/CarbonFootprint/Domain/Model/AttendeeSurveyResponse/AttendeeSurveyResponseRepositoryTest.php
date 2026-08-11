<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\AttendeeSurveyResponse;

use App\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurveyId;
use App\CarbonFootprint\Domain\Model\AttendeeSurveyResponse\AttendeeSurveyResponse;
use App\CarbonFootprint\Domain\Model\AttendeeSurveyResponse\AttendeeSurveyResponseRepository;
use App\CarbonFootprint\Domain\Model\Geo\DistanceSource;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityId;
use App\CarbonFootprint\Domain\Model\Trip\LegKind;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class AttendeeSurveyResponseRepositoryTest extends KernelTestCase
{
    public function testAddAttendeeSurveyResponseToRepository(): void
    {
        $repository = $this->createSUT();

        $attendeeSurveyResponse = AttendeeSurveyResponseMother::random();

        $this->assertNull($repository->find($attendeeSurveyResponse->id()));

        $repository->add($attendeeSurveyResponse);

        $recovered = $repository->find($attendeeSurveyResponse->id());

        $this->assertNotNull($recovered);
        $this->assertEquals($attendeeSurveyResponse->id(), $recovered->id());
    }

    public function testRecoversTheEmissionsSnapshotOfTheResponse(): void
    {
        $repository = $this->createSUT();

        $attendeeSurveyResponse = AttendeeSurveyResponseMother::withEmissions((string) AttendeeSurveyId::generate(), '12.5');

        $repository->add($attendeeSurveyResponse);

        $recovered = $repository->find($attendeeSurveyResponse->id());

        $this->assertNotNull($recovered);
        $this->assertEqualsWithDelta(12.5, (float) $recovered->emissions()->co2eq(), 0.001);
        $this->assertEqualsWithDelta(12.5, (float) $recovered->mobilityEmissions()->co2eq(), 0.001);
        $this->assertEquals($attendeeSurveyResponse->emissionFactorGroupId(), $recovered->emissionFactorGroupId());
        $this->assertSame(2026, $recovered->calculationYear());
    }

    public function testFindByAttendeeSurveyId(): void
    {
        $repository = $this->createSUT();

        $attendeeSurveyId = (string) AttendeeSurveyId::generate();

        $repository->add(AttendeeSurveyResponseMother::fromAttendeeSurveyId($attendeeSurveyId));
        $repository->add(AttendeeSurveyResponseMother::fromAttendeeSurveyId($attendeeSurveyId));
        $repository->add(AttendeeSurveyResponseMother::random());

        $results = $repository->findByAttendeeSurveyId(new AttendeeSurveyId($attendeeSurveyId));

        $this->assertCount(2, $results);
    }

    public function testCountByAttendeeSurveyId(): void
    {
        $repository = $this->createSUT();

        $attendeeSurveyId = (string) AttendeeSurveyId::generate();

        $repository->add(AttendeeSurveyResponseMother::fromAttendeeSurveyId($attendeeSurveyId));
        $repository->add(AttendeeSurveyResponseMother::fromAttendeeSurveyId($attendeeSurveyId));
        $repository->add(AttendeeSurveyResponseMother::random());

        $this->assertSame(2, $repository->countByAttendeeSurveyId(new AttendeeSurveyId($attendeeSurveyId)));
    }

    public function testCountByAttendeeSurveyIdReturnsZeroWhenNoResponses(): void
    {
        $repository = $this->createSUT();

        $this->assertSame(0, $repository->countByAttendeeSurveyId(AttendeeSurveyId::generate()));
    }

    public function testFindByAttendeeSurveyIdRecoversAllTheLegsOfEveryResponse(): void
    {
        $repository = $this->createSUT();

        $attendeeSurveyId = (string) AttendeeSurveyId::generate();

        $repository->add($this->responseWithLegs($attendeeSurveyId, [
            [LegKind::Intercity, DistanceSource::Route],
            [LegKind::Intercity, DistanceSource::Manual],
            [LegKind::City, DistanceSource::Route],
        ]));
        $repository->add($this->responseWithLegs($attendeeSurveyId, [
            [LegKind::City, DistanceSource::GreatCircle],
        ]));

        $results = $repository->findByAttendeeSurveyId(new AttendeeSurveyId($attendeeSurveyId));

        $legCounts = array_map(static fn($response): int => count($response->legs()), $results);
        sort($legCounts);

        $this->assertCount(2, $results);
        $this->assertSame([1, 3], $legCounts);
    }

    public function testCountLegsByDistanceSource(): void
    {
        $repository = $this->createSUT();

        $attendeeSurveyId = (string) AttendeeSurveyId::generate();

        $repository->add($this->responseWithLegs($attendeeSurveyId, [
            [LegKind::Intercity, DistanceSource::Route],
            [LegKind::Intercity, DistanceSource::Manual],
            [LegKind::City, DistanceSource::Route],
        ]));
        $repository->add($this->responseWithLegs($attendeeSurveyId, [
            [LegKind::City, DistanceSource::GreatCircle],
            [LegKind::City, DistanceSource::GreatCircle],
        ]));
        $repository->add($this->responseWithLegs((string) AttendeeSurveyId::generate(), [
            [LegKind::Intercity, DistanceSource::Route],
        ]));

        $counts = $repository->countLegsByDistanceSource(new AttendeeSurveyId($attendeeSurveyId));
        ksort($counts);

        $this->assertSame(
            [
                DistanceSource::GreatCircle->value => 2,
                DistanceSource::Manual->value => 1,
                DistanceSource::Route->value => 2,
            ],
            $counts,
        );
    }

    public function testCountLegsByDistanceSourceReturnsEmptyWhenNoResponses(): void
    {
        $repository = $this->createSUT();

        $this->assertSame([], $repository->countLegsByDistanceSource(AttendeeSurveyId::generate()));
    }

    public function testCountMultimodalResponses(): void
    {
        $repository = $this->createSUT();

        $attendeeSurveyId = (string) AttendeeSurveyId::generate();

        $repository->add($this->responseWithLegs($attendeeSurveyId, [
            [LegKind::Intercity, DistanceSource::Route],
            [LegKind::Intercity, DistanceSource::Manual],
            [LegKind::City, DistanceSource::Route],
        ]));
        $repository->add($this->responseWithLegs($attendeeSurveyId, [
            [LegKind::City, DistanceSource::GreatCircle],
            [LegKind::City, DistanceSource::GreatCircle],
        ]));
        $repository->add($this->responseWithLegs($attendeeSurveyId, [
            [LegKind::Intercity, DistanceSource::Route],
            [LegKind::City, DistanceSource::Route],
        ]));
        $repository->add($this->responseWithLegs((string) AttendeeSurveyId::generate(), [
            [LegKind::Intercity, DistanceSource::Route],
            [LegKind::Intercity, DistanceSource::Route],
        ]));

        $this->assertSame(2, $repository->countMultimodalResponses(new AttendeeSurveyId($attendeeSurveyId)));
    }

    public function testCountMultimodalResponsesReturnsZeroWhenNoResponses(): void
    {
        $repository = $this->createSUT();

        $this->assertSame(0, $repository->countMultimodalResponses(AttendeeSurveyId::generate()));
    }

    /** @param list<array{0: LegKind, 1: DistanceSource}> $legs */
    private function responseWithLegs(string $attendeeSurveyId, array $legs): AttendeeSurveyResponse
    {
        $resolvedLegs = [];

        foreach ($legs as $position => [$kind, $distanceSource]) {
            $resolvedLegs[] = AttendeeSurveyResponseMother::leg(
                position: $position + 1,
                kind: $kind,
                mobilityId: (string) MobilityId::generate(),
                distanceKm: 100.0,
                distanceSource: $distanceSource,
            );
        }

        return AttendeeSurveyResponseMother::from(
            attendeeSurveyId: $attendeeSurveyId,
            cameFromAnotherCity: true,
            intercityTrips: 1,
            movedWithinCity: true,
            cityTrips: 1,
            legs: $resolvedLegs,
        );
    }

    abstract protected function createSUT(): AttendeeSurveyResponseRepository;
}
