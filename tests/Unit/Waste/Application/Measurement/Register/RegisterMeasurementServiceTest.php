<?php

namespace App\Tests\Unit\Waste\Application\Measurement\Register;

use PHPUnit\Framework\TestCase;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Waste\Domain\Model\Measurement\Measurement;
use App\Waste\Domain\Model\Measurement\MeasurementRegistered;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Waste\Domain\Model\Container\ContainerMother;
use App\Tests\Unit\Waste\Domain\Model\Container\ContainerRepositoryMother;
use App\Waste\Application\Measurement\Register\RegisterMeasurementRequest;
use App\Waste\Application\Measurement\Register\RegisterMeasurementService;
use App\Waste\Application\Measurement\Register\RegisterMeasurementResponse;
use App\Tests\Unit\Waste\Domain\Model\Measurement\MeasurementRepositoryMother;

class RegisterMeasurementServiceTest extends TestCase
{
    private $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testUseCase()
    {
        $containerRepository = ContainerRepositoryMother::inMemory();

        $container = ContainerMother::random();

        $containerRepository->add(
            $container
        );

        $measurementRepository = MeasurementRepositoryMother::inMemory();

        $sut = $this->createSUT($containerRepository, $measurementRepository);

        $request = new RegisterMeasurementRequest(
            containerId: $container->id(),
            quantity: 1,
            load: 0.5,
            registeredBy: UserId::generate(),
            justification: 'Fiesta de pijamas!'
        );

        $result = $sut($request);

        $this->assertNotNull($result);
        $this->assertTrue($result instanceof RegisterMeasurementResponse);
        $this->assertNotEmpty((string) $result->measurementId);
        $this->assertEquals(36, strlen((string) $result->measurementId));
        

        $measurementPersisted = $measurementRepository->find($result->measurementId);

        $this->assertNotNull($measurementPersisted);
        $this->assertTrue($measurementPersisted instanceof Measurement);
        
        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof MeasurementRegistered);
        $this->assertTrue($measurementPersisted->id()->equals($lastEvent->measurementId()));
    }

    private function createSUT($containerRepository, $measurementRepository)
    {
        return new RegisterMeasurementService(
            containerRepository: $containerRepository,
            measurementRepository: $measurementRepository
        );
    }
}