<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\ElectricityMarketer\Register;

use App\CarbonFootprint\Application\ElectricityMarketer\ElectricityMarketerAuthorizer;
use App\CarbonFootprint\Application\ElectricityMarketer\Register\RegisterElectricityMarketerRequest;
use App\CarbonFootprint\Application\ElectricityMarketer\Register\RegisterElectricityMarketerService;
use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerRegistered;
use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RegisterElectricityMarketerServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testUseCase(): void
    {
        $repository = ElectricityMarketerRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $request = new RegisterElectricityMarketerRequest(
            name: 'Electricity Marketer 1',
        );

        $result = $sut($request, UserMother::adminUser());

        $this->assertNotEmpty((string) $result->electricityMarketerId);
        $this->assertEquals(36, strlen((string) $result->electricityMarketerId));
        $this->assertEquals('Electricity Marketer 1', $result->name);

        $electricityMarketerPersisted = $repository->find($result->electricityMarketerId);

        $this->assertNotNull($electricityMarketerPersisted);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof ElectricityMarketerRegistered);
        $this->assertEquals('Electricity Marketer 1', $lastEvent->name());
        $this->assertTrue($electricityMarketerPersisted->id()->equals($lastEvent->electricityMarketerId()));
    }

    public function testIsHandle(): void
    {
        $repository = ElectricityMarketerRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $result = $sut->isHandled(new RegisterElectricityMarketerRequest(
            name: 'test',
        ));

        $this->assertTrue($result);
    }

    private function createSUT(ElectricityMarketerRepository $repository): RegisterElectricityMarketerService
    {
        return new RegisterElectricityMarketerService(
            electricityMarketerRepository: $repository,
            authorizer: new ElectricityMarketerAuthorizer(),
        );
    }
}
