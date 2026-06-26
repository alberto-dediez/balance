<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\ElectricityMarketer\Remove;

use App\CarbonFootprint\Application\ElectricityMarketer\ElectricityMarketerAuthorizer;
use App\CarbonFootprint\Application\ElectricityMarketer\Remove\RemoveElectricityMarketerRequest;
use App\CarbonFootprint\Application\ElectricityMarketer\Remove\RemoveElectricityMarketerService;
use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerRemoved;
use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RemoveElectricityMarketerServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testInvoke(): void
    {
        $repository = ElectricityMarketerRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $electricityMarketer = ElectricityMarketerMother::registeredElectricityMarketer();

        $repository->add($electricityMarketer);

        $request = new RemoveElectricityMarketerRequest(
            electricityMarketerId: $electricityMarketer->id(),
        );

        $result = $sut($request, UserMother::adminUser());

        $this->assertTrue($electricityMarketer->id()->equals($result->electricityMarketerId));

        $electricityMarketerPersisted = $repository->find($result->electricityMarketerId);

        $this->assertNull($electricityMarketerPersisted);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof ElectricityMarketerRemoved);
    }

    public function testIsHandle(): void
    {
        $repository = ElectricityMarketerRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $electricityMarketer = ElectricityMarketerMother::random();

        $result = $sut->isHandled(new RemoveElectricityMarketerRequest(
            electricityMarketerId: $electricityMarketer->id(),
        ));

        $this->assertTrue($result);
    }

    private function createSUT(ElectricityMarketerRepository $repository): RemoveElectricityMarketerService
    {
        return new RemoveElectricityMarketerService($repository, new ElectricityMarketerAuthorizer());
    }
}
