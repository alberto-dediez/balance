<?php

namespace App\Tests\Unit\Waste\Application\Measurement\Import;

use App\Property\Domain\Model\Property\PropertyRepository;
use App\Security\Domain\Model\User\UserId;
use App\Security\Domain\Model\User\UserRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\Account\AccountMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Security\Domain\Model\User\UserRepositoryMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Waste\Domain\Model\Container\ContainerRepositoryMother;
use App\Tests\Unit\Waste\Domain\Model\Measurement\MeasurementRepositoryMother;
use App\Tests\Unit\Waste\Domain\Model\Waste\WasteMother;
use App\Tests\Unit\Waste\Domain\Model\Waste\WasteRepositoryMother;
use App\Tests\Unit\Waste\Domain\Model\WasteManager\WasteManagerMother;
use App\Tests\Unit\Waste\Domain\Model\WasteManager\WasteManagerRepositoryMother;
use App\Waste\Application\Measurement\Import\ImportMeasurementRequest;
use App\Waste\Application\Measurement\Import\ImportMeasurementResponse;
use App\Waste\Application\Measurement\Import\ImportMeasurementService;
use App\Waste\Application\Measurement\MeasurementAuthorizer;
use App\Waste\Application\Measurement\Register\RegisterMeasurementRequest;
use App\Waste\Domain\Model\Container\ContainerId;
use App\Waste\Domain\Model\Container\ContainerRepository;
use App\Waste\Domain\Model\Container\Purpose;
use App\Waste\Domain\Model\Measurement\Measurement;
use App\Waste\Domain\Model\Measurement\MeasurementRegistered;
use App\Waste\Domain\Model\Measurement\MeasurementRepository;
use App\Waste\Domain\Model\Waste\WasteRepository;
use App\Waste\Domain\Model\WasteManager\WasteManagerId;
use App\Waste\Domain\Model\WasteManager\WasteManagerRepository;
use PHPUnit\Framework\TestCase;

class ImportMeasurementServiceTest extends TestCase
{
    private $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testUseCase()
    {
        $data = $this->createData();

        $userRepository = UserRepositoryMother::inMemory();
        $measurementRepository = MeasurementRepositoryMother::inMemory();
        $containerRepository = ContainerRepositoryMother::inMemory();
        $propertyRepository = PropertyRepositoryMother::inMemory();
        $wasteRepository = WasteRepositoryMother::inMemory();
        $wasteManagerRepository = WasteManagerRepositoryMother::inMemory();

        $account = AccountMother::registeredAccount();
        $user = UserMother::fromUsernameAndAccount('user', $account);
        $userRepository->add($user);

        $property = PropertyMother::fromNameAndAccountAndId('Hotel', $account->id(), $data['propertyId']);
        $propertyRepository->add($property);

        $waste = WasteMother::fromNameAndId('Papel', $data['wasteId']);
        $wasteRepository->add($waste);

        $wasteManager = WasteManagerMother::fromNameAndIdAndAccount(
            name: 'Basuras SL',
            wasteManagerId: new WasteManagerId($data['wasteManagerId']),
            accountId: $account->id()
        );
        $wasteManagerRepository->add($wasteManager);

        $sut = $this->createSUT(
            measurementRepository: $measurementRepository,
            containerRepository: $containerRepository,
            propertyRepository: $propertyRepository,
            wasteRepository: $wasteRepository,
            wasteManagerRepository: $wasteManagerRepository,
            userRepository: $userRepository
        );

        $request = new ImportMeasurementRequest(
            containerId: $data['containerId'],
            propertyId: $data['propertyId'],
            wasteId: $data['wasteId'],
            purpose: $data['purpose'],
            wasteManagerId: $data['wasteManagerId'],
            quantity: $data['quantity'],
            load: $data['load'],
            justification: $data['justification'],
            unit: $data['unit'],
            day: $data['day'],
            reference: $data['reference']
        );

        $result = $sut($request, $user);

        $this->assertNotNull($result);
        $this->assertTrue($result instanceof ImportMeasurementResponse);
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

    public function testIsHandle()
    {
        $userRepository = UserRepositoryMother::inMemory();
        $measurementRepository = MeasurementRepositoryMother::inMemory();
        $containerRepository = ContainerRepositoryMother::inMemory();
        $propertyRepository = PropertyRepositoryMother::inMemory();
        $wasteRepository = WasteRepositoryMother::inMemory();
        $wasteManagerRepository = WasteManagerRepositoryMother::inMemory();

        $sut = $this->createSUT(
            measurementRepository: $measurementRepository,
            containerRepository: $containerRepository,
            propertyRepository: $propertyRepository,
            wasteRepository: $wasteRepository,
            wasteManagerRepository: $wasteManagerRepository,
            userRepository: $userRepository
        );

        $data = $this->createData();
        $request = new ImportMeasurementRequest(
            containerId: $data['containerId'],
            propertyId: $data['propertyId'],
            wasteId: $data['wasteId'],
            purpose: $data['purpose'],
            wasteManagerId: $data['wasteManagerId'],
            quantity: $data['quantity'],
            load: $data['load'],
            justification: $data['justification'],
            unit: $data['unit'],
            day: $data['day'],
            reference: $data['reference']
        );

        $result = $sut->isHandled($request);

        $this->assertTrue($result);
    }

    private function createSUT(
        MeasurementRepository $measurementRepository,
        ContainerRepository $containerRepository,
        PropertyRepository $propertyRepository,
        WasteRepository $wasteRepository,
        WasteManagerRepository $wasteManagerRepository,
        UserRepository $userRepository
    ) {
        return new ImportMeasurementService(
            measurementRepository: $measurementRepository,
            containerRepository: $containerRepository,
            propertyRepository: $propertyRepository,
            wasteRepository: $wasteRepository,
            wasteManagerRepository: $wasteManagerRepository,
            userRepository: $userRepository,
            authorizer: new MeasurementAuthorizer()
        );
    }

    private function createData(): array
    {
        return [
            'containerId' => null,
            'propertyId' => 'property',
            'wasteId' => 'waste',
            'purpose' => Purpose::Waste->value,
            'wasteManagerId' => 'waste-manager',
            'quantity' => null,
            'load' => '152.23',
            'justification' => 'albaran 666',
            'unit' => 'l',
            'day' => '2025-10-16 10:13:16',
            'reference' => '666',
        ];
    }
}
