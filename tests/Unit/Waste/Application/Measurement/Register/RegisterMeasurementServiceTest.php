<?php

namespace App\Tests\Unit\Waste\Application\Measurement\Register;

use App\Waste\Infrastructure\Persistence\InMemory\Repository\InMemoryContainerRepository;
use App\Waste\Infrastructure\Persistence\InMemory\Repository\InMemoryMeasurementRepository;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyRepository;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryUserRepository;
use PHPUnit\Framework\TestCase;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Waste\Domain\Model\Container\ContainerId;
use App\Waste\Domain\Model\Measurement\Measurement;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Waste\Application\Measurement\MeasurementAuthorizer;
use App\Waste\Domain\Model\Measurement\MeasurementRegistered;
use App\Tests\Unit\Security\Domain\Model\Account\AccountMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Waste\Domain\Model\Container\ContainerMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use App\Tests\Unit\Security\Domain\Model\User\UserRepositoryMother;
use App\Tests\Unit\Waste\Domain\Model\Container\ContainerRepositoryMother;
use App\Waste\Application\Measurement\Register\RegisterMeasurementRequest;
use App\Waste\Application\Measurement\Register\RegisterMeasurementService;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyRepositoryMother;
use App\Waste\Application\Measurement\Register\RegisterMeasurementResponse;
use App\Tests\Unit\Waste\Domain\Model\Measurement\MeasurementRepositoryMother;

class RegisterMeasurementServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testUseCase(): void
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
            justification: 'Fiesta de pijamas!',
            reference: '123'
        );

        $result = $sut($request, $user);

        $this->assertNotNull($result);
        $this->assertTrue($result instanceof RegisterMeasurementResponse);
        $this->assertNotEmpty((string) $result->measurementId);
        $this->assertEquals(36, strlen((string) $result->measurementId));
        $this->assertEquals('123', $result->reference);


        $measurementPersisted = $measurementRepository->find($result->measurementId);

        $this->assertNotNull($measurementPersisted);
        $this->assertTrue($measurementPersisted instanceof Measurement);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof MeasurementRegistered);
        $this->assertTrue($measurementPersisted->id()->equals($lastEvent->measurementId()));
    }

    public function testIsHandle(): void
    {
        $userRepository = UserRepositoryMother::inMemory();
        $measurementRepository = MeasurementRepositoryMother::inMemory();
        $containerRepository = ContainerRepositoryMother::inMemory();
        $propertyRepository = PropertyRepositoryMother::inMemory();

        $sut = $this->createSUT(
            containerRepository: $containerRepository,
            measurementRepository: $measurementRepository,
            propertyRepository: $propertyRepository,
            userRepository: $userRepository
        );

        $result = $sut->isHandled(new RegisterMeasurementRequest(
            containerId: ContainerId::generate(),
            quantity: 1,
            load: 0.5,
            registeredBy: UserId::generate(),
            justification: 'Fiesta de pijamas!'
        ));

        $this->assertTrue($result);
    }

    private function createSUT(InMemoryContainerRepository $containerRepository, InMemoryMeasurementRepository $measurementRepository, InMemoryPropertyRepository $propertyRepository, InMemoryUserRepository $userRepository): RegisterMeasurementService
    {
        return new RegisterMeasurementService(
            measurementRepository: $measurementRepository,
            containerRepository: $containerRepository,
            propertyRepository: $propertyRepository,
            userRepository: $userRepository,
            authorizer: new MeasurementAuthorizer()
        );
    }
}
