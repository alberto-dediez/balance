<?php

namespace App\Tests\Unit\Waste\Application\Waste\Register;

use PHPUnit\Framework\TestCase;
use App\Waste\Domain\Model\Waste\Waste;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Waste\Domain\Model\Waste\WasteRegistered;
use App\Waste\Application\Waste\Register\RegisterWasteRequest;
use App\Waste\Application\Waste\Register\RegisterWasteService;
use App\Waste\Application\Waste\Register\RegisterWasteResponse;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Waste\Domain\Model\Waste\WasteRepositoryMother;

class RegisterWasteServiceTest extends TestCase
{
    private $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testUseCase()
    {
        $repository = WasteRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $request = new RegisterWasteRequest(
            name: 'Company ABC',
            isDangerous: true
        );

        $result = $sut($request);

        $this->assertNotNull($result);
        $this->assertTrue($result instanceof RegisterWasteResponse);
        $this->assertNotEmpty((string) $result->wasteId);
        $this->assertEquals(36, strlen((string) $result->wasteId));
        $this->assertTrue($result->isDangerous);

        $wastePersisted = $repository->find($result->wasteId);

        $this->assertNotNull($wastePersisted);
        $this->assertTrue($wastePersisted instanceof Waste);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof WasteRegistered);
        $this->assertEquals('Company ABC', $lastEvent->name());
        $this->assertTrue($wastePersisted->id()->equals($lastEvent->wasteId()));
        $this->assertTrue($wastePersisted->isDangerous());
    }

    private function createSUT($repository)
    {
        return new RegisterWasteService($repository);
    }
}