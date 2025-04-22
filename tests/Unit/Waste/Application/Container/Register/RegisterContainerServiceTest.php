<?php

namespace App\Tests\Unit\Waste\Application\Container\Register;

use PHPUnit\Framework\TestCase;
use App\Waste\Domain\Model\Waste\WasteId;
use App\Waste\Domain\Model\Container\QrCode;
use App\Waste\Domain\Model\Container\Purpose;
use App\Waste\Domain\Model\Container\Container;
use App\Waste\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Waste\Domain\Model\Container\MeasurementMethod;
use App\Waste\Domain\Model\Property\PropertyRepository;
use App\Waste\Domain\Model\Container\ContainerRegistered;
use App\Waste\Domain\Model\Container\ContainerRepository;
use App\Tests\Unit\Waste\Domain\Model\Property\PropertyMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Waste\Application\Container\Register\RegisterContainerRequest;
use App\Waste\Application\Container\Register\RegisterContainerService;
use App\Waste\Application\Container\Register\RegisterContainerResponse;
use App\Tests\Unit\Waste\Domain\Model\Property\PropertyRepositoryMother;
use App\Tests\Unit\Waste\Domain\Model\Container\ContainerRepositoryMother;
use App\Tests\Unit\Waste\Infrastructure\QrCodeGenerator\FakeQrCodeGenerator;

class RegisterContainerServiceTest extends TestCase
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
        $propertyRepository = PropertyRepositoryMother::inMemory();

        $sut = $this->createSUT($containerRepository, $propertyRepository);

        $accountId = AccountId::generate();

        $property = PropertyMother::fromNameAndAccountAndId('Propiedad', (string) $accountId, 'property');

        $propertyRepository->add($property);

        $request = new RegisterContainerRequest(
            propertyId: $property->id(),
            wasteId: new WasteId('444'),
            purpose: Purpose::Recycling,
            measurementMethod: MeasurementMethod::Weight
        );

        $result = $sut($request);

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
    }

    private function createSUT(ContainerRepository $containerRepository, PropertyRepository $propertyRepository)
    {
        return new RegisterContainerService(
            containerRepository: $containerRepository,
            propertyRepository: $propertyRepository,
            qrCodeGenerator: new FakeQrCodeGenerator()
        );
    }
}