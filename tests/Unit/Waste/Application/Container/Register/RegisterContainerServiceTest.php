<?php

declare(strict_types=1);

namespace App\Tests\Unit\Waste\Application\Container\Register;

use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Waste\Domain\Model\Container\ContainerRepositoryMother;
use App\Tests\Unit\Waste\Infrastructure\QrCode\FakeQrCodeGenerator;
use App\Waste\Application\Container\ContainerAuthorizer;
use App\Waste\Application\Container\Register\RegisterContainerRequest;
use App\Waste\Application\Container\Register\RegisterContainerResponse;
use App\Waste\Application\Container\Register\RegisterContainerService;
use App\Waste\Domain\Model\Container\Container;
use App\Waste\Domain\Model\Container\ContainerRegistered;
use App\Waste\Domain\Model\Container\ContainerRepository;
use App\Waste\Domain\Model\Container\MeasurementMethod;
use App\Waste\Domain\Model\Container\NotValidVolumeException;
use App\Waste\Domain\Model\Container\Purpose;
use App\Waste\Domain\Model\Container\QrCode;
use App\Waste\Domain\Model\Waste\WasteId;
use App\Waste\Domain\Model\WasteManager\WasteManagerId;
use PHPUnit\Framework\TestCase;

class RegisterContainerServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testUseCase(): void
    {
        $containerRepository = ContainerRepositoryMother::inMemory();
        $propertyRepository = PropertyRepositoryMother::inMemory();

        $sut = $this->createSUT($containerRepository, $propertyRepository);

        $accountId = AccountId::generate();

        $property = PropertyMother::fromNameAndAccountAndId('Propiedad', (string) $accountId, 'property');

        $propertyRepository->add($property);

        $user = UserMother::fromUsernameAndRoleAndAccount('user', Role::Owner, $accountId);

        $request = new RegisterContainerRequest(
            propertyId: $property->id(),
            wasteId: new WasteId('444'),
            purpose: Purpose::Recycling,
            measurementMethod: MeasurementMethod::Weight,
            volume: 50,
            wasteManagerId: WasteManagerId::generate(),
        );

        $result = $sut($request, $user);

        $this->assertNotNull($result);
        $this->assertTrue($result instanceof RegisterContainerResponse);
        $this->assertNotEmpty((string) $result->containerId);
        $this->assertEquals(36, strlen((string) $result->containerId));
        $this->assertEquals(50, $result->volume);
        $this->assertNotNull($result->qrCode);
        $this->assertTrue($result->qrCode instanceof QrCode);

        $containerPersisted = $containerRepository->find($result->containerId);

        $this->assertNotNull($containerPersisted);
        $this->assertTrue($containerPersisted instanceof Container);
        $this->assertNotNull($containerPersisted->qrCode());
        $this->assertEquals(50, $containerPersisted->volume());

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof ContainerRegistered);
        $this->assertTrue($containerPersisted->id()->equals($lastEvent->containerId()));
        $this->assertTrue($request->wasteManagerId->equals($lastEvent->wasteManagerId()));
        $this->assertEquals(50, $lastEvent->volume());
    }

    public function testRegisterWeightContainer(): void
    {
        $containerRepository = ContainerRepositoryMother::inMemory();
        $propertyRepository = PropertyRepositoryMother::inMemory();

        $sut = $this->createSUT($containerRepository, $propertyRepository);

        $accountId = AccountId::generate();

        $property = PropertyMother::fromNameAndAccountAndId('Propiedad', (string) $accountId, 'property');

        $propertyRepository->add($property);

        $user = UserMother::fromUsernameAndRoleAndAccount('user', Role::Owner, $accountId);

        $request = new RegisterContainerRequest(
            propertyId: $property->id(),
            wasteId: new WasteId('444'),
            purpose: Purpose::Recycling,
            measurementMethod: MeasurementMethod::Weight,
            volume: null,
            wasteManagerId: WasteManagerId::generate(),
        );

        $result = $sut($request, $user);

        $this->assertNotNull($result);
        $this->assertTrue($result instanceof RegisterContainerResponse);
        $this->assertNotEmpty((string) $result->containerId);
        $this->assertEquals(36, strlen((string) $result->containerId));
        $this->assertNotNull($result->qrCode);
        $this->assertTrue($result->qrCode instanceof QrCode);

        $containerPersisted = $containerRepository->find($result->containerId);

        $this->assertNotNull($containerPersisted);
        $this->assertTrue($containerPersisted instanceof Container);
        $this->assertNotNull($containerPersisted->qrCode());

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof ContainerRegistered);
        $this->assertTrue($containerPersisted->id()->equals($lastEvent->containerId()));
        $this->assertTrue($request->wasteManagerId->equals($lastEvent->wasteManagerId()));
    }

    public function testRegisterVolumeContainerWithoutVolume(): void
    {
        $this->expectException(NotValidVolumeException::class);

        $containerRepository = ContainerRepositoryMother::inMemory();
        $propertyRepository = PropertyRepositoryMother::inMemory();

        $sut = $this->createSUT($containerRepository, $propertyRepository);

        $accountId = AccountId::generate();

        $property = PropertyMother::fromNameAndAccountAndId('Propiedad', (string) $accountId, 'property');

        $propertyRepository->add($property);

        $user = UserMother::fromUsernameAndRoleAndAccount('user', Role::Owner, $accountId);

        $request = new RegisterContainerRequest(
            propertyId: $property->id(),
            wasteId: new WasteId('444'),
            purpose: Purpose::Recycling,
            measurementMethod: MeasurementMethod::Volume,
            volume: null,
            wasteManagerId: WasteManagerId::generate(),
        );

        $sut($request, $user);
    }

    public function testIsHandle(): void
    {
        $propertyRepository = PropertyRepositoryMother::inMemory();
        $containerRepository = ContainerRepositoryMother::inMemory();

        $sut = $this->createSUT($containerRepository, $propertyRepository);

        $result = $sut->isHandled(new RegisterContainerRequest(
            propertyId: PropertyId::generate(),
            wasteId: WasteId::generate(),
            purpose: Purpose::Reuse,
            measurementMethod: MeasurementMethod::Volume,
            volume: 10,
            wasteManagerId: WasteManagerId::generate(),
        ));

        $this->assertTrue($result);
    }

    private function createSUT(ContainerRepository $containerRepository, PropertyRepository $propertyRepository): RegisterContainerService
    {
        return new RegisterContainerService(
            containerRepository: $containerRepository,
            propertyRepository: $propertyRepository,
            qrCodeGenerator: new FakeQrCodeGenerator(),
            authorizer: new ContainerAuthorizer(),
        );
    }
}
