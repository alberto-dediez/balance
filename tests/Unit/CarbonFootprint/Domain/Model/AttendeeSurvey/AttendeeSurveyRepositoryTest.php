<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\AttendeeSurvey;

use App\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurveyFilter;
use App\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurveyRepository;
use App\EventOperations\Domain\Model\Event\EventId;
use App\Security\Domain\Model\Account\AccountId;
use PHPUnit\Framework\TestCase;

abstract class AttendeeSurveyRepositoryTest extends TestCase
{
    public function testAddAttendeeSurveyToRepository(): void
    {
        $repository = $this->createSUT();

        $attendeeSurvey = AttendeeSurveyMother::registeredAttendeeSurvey();

        $this->assertNull($repository->find($attendeeSurvey->id()));

        $repository->add($attendeeSurvey);

        $recovered = $repository->find($attendeeSurvey->id());

        $this->assertNotNull($recovered);
        $this->assertEquals($attendeeSurvey->id(), $recovered->id());
    }

    public function testFindByEventId(): void
    {
        $repository = $this->createSUT();

        $eventId = (string) EventId::generate();
        $attendeeSurvey = AttendeeSurveyMother::fromEventId($eventId);

        $repository->add($attendeeSurvey);

        $recovered = $repository->findByEventId(new EventId($eventId));

        $this->assertNotNull($recovered);
        $this->assertEquals($attendeeSurvey->id(), $recovered->id());
    }

    public function testFindByEventIdReturnsNullWhenNotFound(): void
    {
        $repository = $this->createSUT();

        $this->assertNull($repository->findByEventId(EventId::generate()));
    }

    public function testFindByFilterByAccountId(): void
    {
        $repository = $this->createSUT();

        $accountId = (string) AccountId::generate();
        $repository->add(AttendeeSurveyMother::fromEventIdAndAccount((string) EventId::generate(), $accountId));
        $repository->add(AttendeeSurveyMother::fromEventIdAndAccount((string) EventId::generate(), $accountId));
        $repository->add(AttendeeSurveyMother::fromEventIdAndAccount((string) EventId::generate(), (string) AccountId::generate()));

        $filter = new AttendeeSurveyFilter(accountId: new AccountId($accountId), limit: 0);

        $this->assertCount(2, $repository->findByFilter($filter));
        $this->assertSame(2, $repository->totalResultsByFilter($filter));
        $this->assertSame(3, $repository->totalResultsByFilter(new AttendeeSurveyFilter(limit: 0)));
    }

    public function testFindByToken(): void
    {
        $repository = $this->createSUT();

        $attendeeSurvey = AttendeeSurveyMother::registeredAttendeeSurvey();

        $repository->add($attendeeSurvey);

        $recovered = $repository->findByToken($attendeeSurvey->token());

        $this->assertNotNull($recovered);
        $this->assertEquals($attendeeSurvey->id(), $recovered->id());
    }

    public function testFindByTokenReturnsNullWhenNotFound(): void
    {
        $repository = $this->createSUT();

        $this->assertNull($repository->findByToken('invalid-token'));
    }

    abstract protected function createSUT(): AttendeeSurveyRepository;
}
