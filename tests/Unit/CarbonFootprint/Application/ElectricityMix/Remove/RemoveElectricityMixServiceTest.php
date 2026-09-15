<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\ElectricityMix\Remove;

use App\CarbonFootprint\Application\ElectricityMix\ElectricityMixAuthorizer;
use App\CarbonFootprint\Application\ElectricityMix\Remove\RemoveElectricityMixRequest;
use App\CarbonFootprint\Application\ElectricityMix\Remove\RemoveElectricityMixService;
use App\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMixRemoved;
use App\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMixRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMixMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMixRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RemoveElectricityMixServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testInvoke(): void
    {
        $repository = ElectricityMixRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $electricityMix = ElectricityMixMother::registeredElectricityMix();

        $repository->add($electricityMix);

        $request = new RemoveElectricityMixRequest(
            electricityMixId: $electricityMix->id(),
        );

        $result = $sut($request, UserMother::adminUser());

        $this->assertTrue($electricityMix->id()->equals($result->electricityMixId));

        $electricityMixPersisted = $repository->find($result->electricityMixId);

        $this->assertNull($electricityMixPersisted);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof ElectricityMixRemoved);
    }

    public function testIsHandle(): void
    {
        $repository = ElectricityMixRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $electricityMix = ElectricityMixMother::random();

        $result = $sut->isHandled(new RemoveElectricityMixRequest(
            electricityMixId: $electricityMix->id(),
        ));

        $this->assertTrue($result);
    }

    private function createSUT(ElectricityMixRepository $repository): RemoveElectricityMixService
    {
        return new RemoveElectricityMixService($repository, new ElectricityMixAuthorizer());
    }
}
