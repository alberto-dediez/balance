<?php

declare(strict_types=1);

namespace App\Tests\Unit\Waste\Application\AlertCheckQueue\Remove;

use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Waste\Domain\Model\AlertCheckQueue\AlertCheckQueueMother;
use App\Tests\Unit\Waste\Domain\Model\AlertCheckQueue\AlertCheckQueueRepositoryMother;
use App\Waste\Application\AlertCheckQueue\AlertCheckQueueAuthorizer;
use App\Waste\Application\AlertCheckQueue\Remove\RemoveAlertCheckQueueRequest;
use App\Waste\Application\AlertCheckQueue\Remove\RemoveAlertCheckQueueService;
use App\Waste\Domain\Model\AlertCheckQueue\AlertCheckQueueId;
use App\Waste\Domain\Model\AlertCheckQueue\AlertCheckQueueRemoved;
use App\Waste\Domain\Model\AlertCheckQueue\AlertCheckQueueRepository;
use PHPUnit\Framework\TestCase;

class RemoveAlertCheckQueueServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testUseCase(): void
    {
        $repository = AlertCheckQueueRepositoryMother::inMemory();

        $entry = AlertCheckQueueMother::pendingEntry();
        $repository->add($entry);

        $sut = $this->createSUT($repository);
        $user = UserMother::adminUser();

        $result = $sut(new RemoveAlertCheckQueueRequest($entry->id()), $user);

        $this->assertTrue($entry->id()->equals($result->alertCheckQueueId));
        $this->assertNull($repository->find($entry->id()));

        $lastEvent = $this->spySubscriber->lastEvent();
        $this->assertNotNull($lastEvent);
        $this->assertInstanceOf(AlertCheckQueueRemoved::class, $lastEvent);
    }

    public function testReturnsResponseWithRequestIdWhenEntryNotFound(): void
    {
        $repository = AlertCheckQueueRepositoryMother::inMemory();
        $sut = $this->createSUT($repository);
        $user = UserMother::adminUser();

        $nonExistentId = AlertCheckQueueId::generate();
        $result = $sut(new RemoveAlertCheckQueueRequest($nonExistentId), $user);

        $this->assertTrue($nonExistentId->equals($result->alertCheckQueueId));
        $this->assertNull($this->spySubscriber->lastEvent());
    }

    public function testIsHandle(): void
    {
        $sut = $this->createSUT(AlertCheckQueueRepositoryMother::inMemory());

        $this->assertTrue($sut->isHandled(new RemoveAlertCheckQueueRequest(AlertCheckQueueId::generate())));
    }

    private function createSUT(AlertCheckQueueRepository $repository): RemoveAlertCheckQueueService
    {
        return new RemoveAlertCheckQueueService(
            alertCheckQueueRepository: $repository,
            authorizer: new AlertCheckQueueAuthorizer(),
        );
    }
}
