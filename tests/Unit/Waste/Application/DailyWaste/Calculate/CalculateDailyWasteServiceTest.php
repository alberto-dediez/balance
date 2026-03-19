<?php

declare(strict_types=1);

namespace App\Tests\Unit\Waste\Application\DailyWaste\Calculate;

use App\Property\Domain\Model\Property\PropertyRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\Account\AccountMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Waste\Domain\Model\Container\ContainerMother;
use App\Tests\Unit\Waste\Domain\Model\Container\ContainerRepositoryMother;
use App\Tests\Unit\Waste\Domain\Model\DailyWaste\DailyWasteMother;
use App\Tests\Unit\Waste\Domain\Model\DailyWaste\DailyWasteRepositoryMother;
use App\Tests\Unit\Waste\Domain\Model\Measurement\MeasurementMother;
use App\Tests\Unit\Waste\Domain\Model\Measurement\MeasurementRepositoryMother;
use App\Tests\Unit\Waste\Domain\Model\Waste\WasteMother;
use App\Tests\Unit\Waste\Domain\Model\Waste\WasteRepositoryMother;
use App\Waste\Application\DailyWaste\Calculate\CalculateDailyWasteRequest;
use App\Waste\Application\DailyWaste\Calculate\CalculateDailyWasteResponse;
use App\Waste\Application\DailyWaste\Calculate\CalculateDailyWasteService;
use App\Waste\Application\DailyWaste\DailyWasteAuthorizer;
use App\Waste\Domain\Model\Container\ContainerRepository;
use App\Waste\Domain\Model\DailyWaste\DailyWaste;
use App\Waste\Domain\Model\DailyWaste\DailyWasteRegistered;
use App\Waste\Domain\Model\DailyWaste\DailyWasteRepository;
use App\Waste\Domain\Model\DailyWaste\DailyWasteUpdated;
use App\Waste\Domain\Model\Measurement\MeasurementRepository;
use App\Waste\Domain\Model\Waste\WasteRepository;
use PHPUnit\Framework\TestCase;

class CalculateDailyWasteServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testUseCaseNewDailyWaste(): void
    {
        $dailyWasteRepository = DailyWasteRepositoryMother::inMemory();
        $measurementRepository = MeasurementRepositoryMother::inMemory();
        $propertyRepository = PropertyRepositoryMother::inMemory();
        $wasteRepository = WasteRepositoryMother::inMemory();
        $containerRepository = ContainerRepositoryMother::inMemory();

        $account = AccountMother::registeredAccount();

        $measurement = MeasurementMother::fromAccount($account->id());
        $measurementRepository->add($measurement);

        $container = ContainerMother::from(
            accountId: $measurement->accountId(),
            containerId: $measurement->containerId(),
            wasteId: $measurement->wasteId(),
            propertyId: $measurement->propertyId(),
            wasteManagerId: $measurement->wasteManagerId(),
            purpose: $measurement->purpose(),
            measurementMethod: $measurement->measurementMethod(),
            volume: 100,
        );
        $containerRepository->add($container);

        $user = UserMother::fromUsernameAndAccount('user', $account);

        $property = PropertyMother::fromNameAndAccountAndId('Hotel', (string) $account->id(), (string) $measurement->propertyId());
        $propertyRepository->add($property);

        $waste = WasteMother::fromDensityAndId((string) 1.34, (string) $measurement->wasteId());
        $wasteRepository->add($waste);

        $sut = $this->createSUT(
            measurementRepository: $measurementRepository,
            dailyWasteRepository: $dailyWasteRepository,
            propertyRepository: $propertyRepository,
            wasteRepository: $wasteRepository,
            containerRepository: $containerRepository,
        );

        $request = new CalculateDailyWasteRequest(
            propertyId: $measurement->propertyId(),
            wasteId: $measurement->wasteId(),
            wasteManagerId: $measurement->wasteManagerId(),
            accountId: $measurement->accountId(),
            purpose: $measurement->purpose(),
            day: $measurement->day(),
        );

        $result = $sut($request, $user);

        $this->assertNotNull($result);
        $this->assertTrue($result instanceof CalculateDailyWasteResponse);
        $this->assertNotEmpty((string) $result->dailyWasteId);
        $this->assertEquals(36, strlen((string) $result->dailyWasteId));

        $dailyWastePersisted = $dailyWasteRepository->find($result->dailyWasteId);

        $this->assertNotNull($dailyWastePersisted);
        $this->assertTrue($dailyWastePersisted instanceof DailyWaste);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof DailyWasteRegistered);
        $this->assertTrue($dailyWastePersisted->id()->equals($lastEvent->dailyWasteId()));
    }

    public function testUseCaseUpdateDailyWaste(): void
    {
        $dailyWasteRepository = DailyWasteRepositoryMother::inMemory();
        $measurementRepository = MeasurementRepositoryMother::inMemory();
        $propertyRepository = PropertyRepositoryMother::inMemory();
        $wasteRepository = WasteRepositoryMother::inMemory();
        $containerRepository = ContainerRepositoryMother::inMemory();

        $dailyWaste = DailyWasteMother::random();
        $dailyWasteRepository->add($dailyWaste);

        $account = AccountMother::fromNameAndId('account', (string) $dailyWaste->accountId());

        $measurement = MeasurementMother::fromDay($dailyWaste);
        $measurementRepository->add($measurement);

        $container = ContainerMother::from(
            accountId: $measurement->accountId(),
            containerId: $measurement->containerId(),
            wasteId: $measurement->wasteId(),
            propertyId: $measurement->propertyId(),
            wasteManagerId: $measurement->wasteManagerId(),
            purpose: $measurement->purpose(),
            measurementMethod: $measurement->measurementMethod(),
            volume: 100,
        );
        $containerRepository->add($container);

        $user = UserMother::fromUsernameAndAccount('user', $account);

        $property = PropertyMother::fromNameAndAccountAndId('Hotel', (string) $account->id(), (string) $measurement->propertyId());
        $propertyRepository->add($property);

        $waste = WasteMother::fromDensityAndId((string) 1.34, (string) $measurement->wasteId());
        $wasteRepository->add($waste);

        $sut = $this->createSUT(
            measurementRepository: $measurementRepository,
            dailyWasteRepository: $dailyWasteRepository,
            propertyRepository: $propertyRepository,
            wasteRepository: $wasteRepository,
            containerRepository: $containerRepository,
        );

        $request = new CalculateDailyWasteRequest(
            propertyId: $measurement->propertyId(),
            wasteId: $measurement->wasteId(),
            wasteManagerId: $measurement->wasteManagerId(),
            accountId: $measurement->accountId(),
            purpose: $measurement->purpose(),
            day: $measurement->day(),
        );

        $result = $sut($request, $user);

        $this->assertNotNull($result);
        $this->assertTrue($result instanceof CalculateDailyWasteResponse);
        $this->assertNotEmpty((string) $result->dailyWasteId);
        $this->assertEquals(36, strlen((string) $result->dailyWasteId));

        $dailyWastePersisted = $dailyWasteRepository->find($result->dailyWasteId);

        $this->assertNotNull($dailyWastePersisted);
        $this->assertTrue($dailyWastePersisted instanceof DailyWaste);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof DailyWasteUpdated);
        $this->assertTrue($dailyWastePersisted->id()->equals($lastEvent->dailyWasteId()));
    }

    public function testIsHandle(): void
    {
        $dailyWasteRepository = DailyWasteRepositoryMother::inMemory();
        $measurementRepository = MeasurementRepositoryMother::inMemory();
        $propertyRepository = PropertyRepositoryMother::inMemory();
        $wasteRepository = WasteRepositoryMother::inMemory();
        $containerRepository = ContainerRepositoryMother::inMemory();

        $sut = $this->createSUT(
            measurementRepository: $measurementRepository,
            dailyWasteRepository: $dailyWasteRepository,
            propertyRepository: $propertyRepository,
            wasteRepository: $wasteRepository,
            containerRepository: $containerRepository,
        );

        $measurement = MeasurementMother::random();

        $result = $sut->isHandled(new CalculateDailyWasteRequest(
            propertyId: $measurement->propertyId(),
            wasteId: $measurement->wasteId(),
            wasteManagerId: $measurement->wasteManagerId(),
            accountId: $measurement->accountId(),
            purpose: $measurement->purpose(),
            day: $measurement->day(),
        ));

        $this->assertTrue($result);
    }

    private function createSUT(
        MeasurementRepository $measurementRepository,
        DailyWasteRepository $dailyWasteRepository,
        PropertyRepository $propertyRepository,
        WasteRepository $wasteRepository,
        ContainerRepository $containerRepository,
    ): CalculateDailyWasteService {
        return new CalculateDailyWasteService(
            dailyWasteRepository: $dailyWasteRepository,
            containerRepository: $containerRepository,
            measurementRepository: $measurementRepository,
            propertyRepository: $propertyRepository,
            wasteRepository: $wasteRepository,
            authorizer: new DailyWasteAuthorizer(),
        );
    }
}
