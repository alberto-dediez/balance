<?php

namespace App\Tests\Unit\Waste\Application\Measurement\Register;

use PHPUnit\Framework\TestCase;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Waste\Domain\Model\Measurement\Measurement;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Waste\Domain\Model\Measurement\MeasurementRegistered;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use App\Tests\Unit\Security\Domain\Model\Account\AccountMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Waste\Domain\Model\Container\ContainerMother;
use App\Tests\Unit\Security\Domain\Model\User\UserRepositoryMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyRepositoryMother;
use App\Tests\Unit\Waste\Domain\Model\Container\ContainerRepositoryMother;
use App\Waste\Application\Measurement\Register\RegisterMeasurementRequest;
use App\Waste\Application\Measurement\Register\RegisterMeasurementService;
use App\Waste\Application\Measurement\Register\RegisterMeasurementResponse;
use App\Tests\Unit\Waste\Domain\Model\Measurement\MeasurementRepositoryMother;
use App\Waste\Application\Measurement\MeasurementAuthorizer;

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
        $userRepository = UserRepositoryMother::inMemory();
        $measurementRepository = MeasurementRepositoryMother::inMemory();
        $containerRepository = ContainerRepositoryMother::inMemory();
        $propertyRepository = PropertyRepositoryMother::inMemory();

        $container = ContainerMother::random();

        $containerRepository->add(
            $container
        );

        $account = AccountMother::registeredAccount();

        $user = UserMother::fromUsernameAndAccount('user', $account);

        $userRepository->add($user);

        $property = PropertyMother::fromNameAndAccountAndId('Hotel', $account->id(), (string) $container->propertyId());

        $propertyRepository->add($property);

        $sut = $this->createSUT(
            containerRepository: $containerRepository,
            measurementRepository: $measurementRepository,
            propertyRepository: $propertyRepository,
            userRepository: $userRepository
        );

        $request = new RegisterMeasurementRequest(
            containerId: $container->id(),
            quantity: 1,
            load: 0.5,
            registeredBy: $user->id(),
            justification: 'Fiesta de pijamas!'
        );

        $result = $sut($request, $user);

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

    private function createSUT($containerRepository, $measurementRepository, $propertyRepository, $userRepository)
    {
        return new RegisterMeasurementService(
            containerRepository: $containerRepository,
            measurementRepository: $measurementRepository,
            propertyRepository: $propertyRepository,
            userRepository: $userRepository,
            authorizer: new MeasurementAuthorizer()
        );
    }
}