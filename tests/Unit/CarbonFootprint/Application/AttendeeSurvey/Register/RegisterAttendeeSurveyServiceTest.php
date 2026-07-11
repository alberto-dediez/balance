<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\AttendeeSurvey\Register;

use App\CarbonFootprint\Application\AttendeeSurvey\Register\RegisterAttendeeSurveyRequest;
use App\CarbonFootprint\Application\AttendeeSurvey\Register\RegisterAttendeeSurveyService;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryAttendeeSurveyRepository;
use App\EventOperations\Domain\Model\Event\EventId;
use App\EventOperations\Infrastructure\Persistence\InMemory\Repository\InMemoryEventRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\EventOperations\Domain\Model\Event\EventMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class RegisterAttendeeSurveyServiceTest extends TestCase
{
    private InMemoryAttendeeSurveyRepository $attendeeSurveyRepository;
    private InMemoryEventRepository $eventRepository;
    private RegisterAttendeeSurveyService $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->attendeeSurveyRepository = new InMemoryAttendeeSurveyRepository();
        $this->eventRepository = new InMemoryEventRepository();

        $this->sut = new RegisterAttendeeSurveyService(
            $this->attendeeSurveyRepository,
            $this->eventRepository,
        );
    }

    public function testRegistersASurveyForANewEvent(): void
    {
        $event = EventMother::fromNameAndAccountAndId('Event', 'account-id', (string) EventId::generate());
        $this->eventRepository->add($event);

        $response = ($this->sut)(new RegisterAttendeeSurveyRequest($event->id()), $this->ownerUser());

        $surveyEventId = $response->attendeeSurvey->eventId();
        $this->assertNotNull($surveyEventId);
        $this->assertTrue($event->id()->equals($surveyEventId));
        $this->assertNotSame('', $response->attendeeSurvey->token());
        $this->assertFalse($response->attendeeSurvey->status()->isClosed());

        $persisted = $this->attendeeSurveyRepository->findByEventId($event->id());
        $this->assertNotNull($persisted);
    }

    public function testIsIdempotentAndReturnsTheExistingSurvey(): void
    {
        $event = EventMother::fromNameAndAccountAndId('Event', 'account-id', (string) EventId::generate());
        $this->eventRepository->add($event);

        $first = ($this->sut)(new RegisterAttendeeSurveyRequest($event->id()), $this->ownerUser());
        $second = ($this->sut)(new RegisterAttendeeSurveyRequest($event->id()), $this->ownerUser());

        $this->assertTrue($first->attendeeSurvey->id()->equals($second->attendeeSurvey->id()));
    }

    public function testThrowsWhenEventDoesNotExist(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ($this->sut)(new RegisterAttendeeSurveyRequest(EventId::generate()), $this->ownerUser());
    }

    private function ownerUser(): User
    {
        return UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id'));
    }
}
