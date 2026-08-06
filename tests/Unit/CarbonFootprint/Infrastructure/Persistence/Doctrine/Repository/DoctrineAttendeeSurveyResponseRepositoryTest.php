<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Infrastructure\Persistence\Doctrine\Repository;

use App\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurveyId;
use App\CarbonFootprint\Domain\Model\AttendeeSurveyResponse\AttendeeSurveyResponse;
use App\CarbonFootprint\Domain\Model\AttendeeSurveyResponse\AttendeeSurveyResponseRepository;
use App\CarbonFootprint\Domain\Model\Geo\DistanceSource;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityId;
use App\CarbonFootprint\Domain\Model\Trip\LegKind;
use App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Repository\DoctrineAttendeeSurveyResponseRepository;
use App\Tests\Unit\CarbonFootprint\Domain\Model\AttendeeSurveyResponse\AttendeeSurveyResponseMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\AttendeeSurveyResponse\AttendeeSurveyResponseRepositoryTest;
use Doctrine\Bundle\DoctrineBundle\Middleware\BacktraceDebugDataHolder;
use Doctrine\ORM\EntityManagerInterface;

class DoctrineAttendeeSurveyResponseRepositoryTest extends AttendeeSurveyResponseRepositoryTest
{
    public function testFindByAttendeeSurveyIdFetchesTheLegsWithoutExtraQueries(): void
    {
        $manager = $this->getContainer()->get(EntityManagerInterface::class);
        $repository = new DoctrineAttendeeSurveyResponseRepository($manager);

        $attendeeSurveyId = (string) AttendeeSurveyId::generate();

        for ($i = 0; $i < 3; ++$i) {
            $repository->add($this->responseWithTwoLegs($attendeeSurveyId));
        }

        $manager->clear();

        $debugDataHolder = $this->debugDataHolder();
        $debugDataHolder->reset();

        $responses = $repository->findByAttendeeSurveyId(new AttendeeSurveyId($attendeeSurveyId));

        $legCount = 0;

        foreach ($responses as $response) {
            $legCount += count($response->legs());
        }

        $this->assertCount(3, $responses);
        $this->assertSame(6, $legCount);
        $this->assertSame(1, $this->executedQueries($debugDataHolder), 'Las legs deben venir en la misma query, sin N+1.');
    }

    public function testFindDoesNotLoadTheLegsUntilTheyAreUsed(): void
    {
        $manager = $this->getContainer()->get(EntityManagerInterface::class);
        $repository = new DoctrineAttendeeSurveyResponseRepository($manager);

        $attendeeSurveyResponse = $this->responseWithTwoLegs((string) AttendeeSurveyId::generate());
        $repository->add($attendeeSurveyResponse);

        $manager->clear();

        $debugDataHolder = $this->debugDataHolder();
        $debugDataHolder->reset();

        $recovered = $repository->find($attendeeSurveyResponse->id());

        $this->assertNotNull($recovered);
        $this->assertSame(
            1,
            $this->executedQueries($debugDataHolder),
            'La coleccion de legs debe ser lazy: recuperar una respuesta suelta no debe cargarlas.',
        );

        $this->assertCount(2, $recovered->legs());
        $this->assertSame(
            2,
            $this->executedQueries($debugDataHolder),
            'Al usar las legs debe ejecutarse exactamente una query adicional.',
        );
    }

    protected function createSUT(): AttendeeSurveyResponseRepository
    {
        $manager = $this->getContainer()->get(EntityManagerInterface::class);

        return new DoctrineAttendeeSurveyResponseRepository($manager);
    }

    private function responseWithTwoLegs(string $attendeeSurveyId): AttendeeSurveyResponse
    {
        return AttendeeSurveyResponseMother::from(
            attendeeSurveyId: $attendeeSurveyId,
            cameFromAnotherCity: true,
            intercityTrips: 1,
            movedWithinCity: true,
            cityTrips: 1,
            legs: [
                AttendeeSurveyResponseMother::leg(1, LegKind::Intercity, (string) MobilityId::generate(), 100.0, distanceSource: DistanceSource::Route),
                AttendeeSurveyResponseMother::leg(2, LegKind::City, (string) MobilityId::generate(), 10.0, distanceSource: DistanceSource::Manual),
            ],
        );
    }

    private function debugDataHolder(): BacktraceDebugDataHolder
    {
        $debugDataHolder = $this->getContainer()->get('doctrine.debug_data_holder');

        $this->assertInstanceOf(BacktraceDebugDataHolder::class, $debugDataHolder);

        return $debugDataHolder;
    }

    private function executedQueries(BacktraceDebugDataHolder $debugDataHolder): int
    {
        $queries = 0;

        foreach ($debugDataHolder->getData() as $connectionQueries) {
            $queries += count($connectionQueries);
        }

        return $queries;
    }
}
