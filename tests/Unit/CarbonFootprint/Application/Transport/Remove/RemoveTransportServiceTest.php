<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Transport\Remove;

use App\CarbonFootprint\Application\Transport\Remove\RemoveTransportRequest;
use App\CarbonFootprint\Application\Transport\Remove\RemoveTransportService;
use App\CarbonFootprint\Application\Transport\TransportAuthorizer;
use App\CarbonFootprint\Domain\Model\Transport\TransportRemoved;
use App\CarbonFootprint\Domain\Model\Transport\TransportRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Transport\TransportMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Transport\TransportRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RemoveTransportServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testInvoke(): void
    {
        $repository = TransportRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $transport = TransportMother::registeredTransport();

        $repository->add($transport);

        $request = new RemoveTransportRequest(
            transportId: $transport->id(),
        );

        $result = $sut($request, UserMother::adminUser());

        $this->assertTrue($transport->id()->equals($result->transportId));

        $transportPersisted = $repository->find($result->transportId);

        $this->assertNull($transportPersisted);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof TransportRemoved);
    }

    public function testIsHandle(): void
    {
        $repository = TransportRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $transport = TransportMother::random();

        $result = $sut->isHandled(new RemoveTransportRequest(
            transportId: $transport->id(),
        ));

        $this->assertTrue($result);
    }

    private function createSUT(TransportRepository $repository): RemoveTransportService
    {
        return new RemoveTransportService($repository, new TransportAuthorizer());
    }
}
