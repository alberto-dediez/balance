<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\MachineryFuel\Remove;

use App\CarbonFootprint\Application\MachineryFuel\MachineryFuelAuthorizer;
use App\CarbonFootprint\Application\MachineryFuel\Remove\RemoveMachineryFuelRequest;
use App\CarbonFootprint\Application\MachineryFuel\Remove\RemoveMachineryFuelService;
use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuelRemoved;
use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuelRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuelMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuelRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RemoveMachineryFuelServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testInvoke(): void
    {
        $repository = MachineryFuelRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $machineryFuel = MachineryFuelMother::registeredMachineryFuel();

        $repository->add($machineryFuel);

        $request = new RemoveMachineryFuelRequest(
            machineryFuelId: $machineryFuel->id(),
        );

        $result = $sut($request, UserMother::adminUser());

        $this->assertTrue($machineryFuel->id()->equals($result->machineryFuelId));

        $persisted = $repository->find($result->machineryFuelId);

        $this->assertNull($persisted);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof MachineryFuelRemoved);
    }

    public function testIsHandle(): void
    {
        $repository = MachineryFuelRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $machineryFuel = MachineryFuelMother::random();

        $result = $sut->isHandled(new RemoveMachineryFuelRequest(
            machineryFuelId: $machineryFuel->id(),
        ));

        $this->assertTrue($result);
    }

    private function createSUT(MachineryFuelRepository $repository): RemoveMachineryFuelService
    {
        return new RemoveMachineryFuelService($repository, new MachineryFuelAuthorizer());
    }
}
