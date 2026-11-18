<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Domain\Event;

use App\Security\Application\RecordedEvent\Register\RegisterRecordedEventService;
use App\Security\Domain\Event\RecordEventSubscriber;
use App\Security\Domain\Model\RecordedEvent\RecordedEventFilter;
use App\Security\Domain\Model\User\UserId;
use App\Security\Domain\Model\User\UserRemoved;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryRecordedEventRepository;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryUserRepository;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Application\Assistant\CurrentAssistantExecution;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Infrastructure\Assistant\ResettableCurrentAssistantExecution;
use App\Tests\Unit\Security\Application\RecordedEvent\RecordedEventAuthorizerMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;

class RecordEventSubscriberTest extends TestCase
{
    private InMemoryRecordedEventRepository $repository;

    private CurrentAssistantExecution $execution;

    private RecordEventSubscriber $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->repository = new InMemoryRecordedEventRepository();
        $this->execution = new ResettableCurrentAssistantExecution();

        $loggedUser = new LoggedUser($this->createMock(Security::class), new InMemoryUserRepository());
        $loggedUser->forceUser(UserMother::adminUser());

        $this->sut = new RecordEventSubscriber(
            $loggedUser,
            new RegisterRecordedEventService($this->repository, RecordedEventAuthorizerMother::create()),
            $this->execution,
        );
    }

    protected function tearDown(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testRecordsTheExecutionOfTheAssistantWhileItIsRunning(): void
    {
        $this->execution->begin('0190f3a4-1b2c-7d3e-8f40-123456789abc');
        $this->sut->handle(new UserRemoved(UserId::generate()));
        $this->execution->end();
        $this->sut->handle(new UserRemoved(UserId::generate()));

        $byAssistant = $this->repository->findByFilter(new RecordedEventFilter(byAssistant: true));
        $notByAssistant = $this->repository->findByFilter(new RecordedEventFilter(byAssistant: false));

        $this->assertCount(1, $byAssistant);
        $this->assertSame('0190f3a4-1b2c-7d3e-8f40-123456789abc', $byAssistant[0]->assistantExecutionId());
        $this->assertCount(1, $notByAssistant);
    }
}
