<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\AttendeeSurveyResponse;

use App\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurveyId;
use App\CarbonFootprint\Domain\Model\AttendeeSurveyResponse\AttendeeSurveyResponseRepository;
use PHPUnit\Framework\TestCase;

abstract class AttendeeSurveyResponseRepositoryTest extends TestCase
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

    abstract protected function createSUT(): AttendeeSurveyResponseRepository;
}
