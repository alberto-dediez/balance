<?php

declare(strict_types=1);

namespace App\Tests\Unit\Waste\Application\Alert\Check\Checker;

use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\UserId;
use App\Tests\Unit\Waste\Domain\Model\Alert\AlertRepositoryMother;
use App\Tests\Unit\Waste\Domain\Model\AlertConfiguration\AlertConfigurationMother;
use App\Tests\Unit\Waste\Domain\Model\Container\ContainerMother;
use App\Tests\Unit\Waste\Domain\Model\Container\ContainerRepositoryMother;
use App\Tests\Unit\Waste\Domain\Model\Measurement\MeasurementMother;
use App\Tests\Unit\Waste\Domain\Model\Measurement\MeasurementRepositoryMother;
use App\Waste\Application\Alert\Check\Checker\UnusedContainerAlertChecker;
use App\Waste\Domain\Model\Container\ContainerId;
use App\Waste\Domain\Model\Measurement\MeasurementId;
use App\Waste\Domain\Model\Waste\WasteId;
use App\Waste\Domain\Model\WasteManager\WasteManagerId;
use PHPUnit\Framework\TestCase;

class UnusedContainerAlertCheckerTest extends TestCase
{
    public function testEmitsAlertWhenContainerHasNoMeasurementsInPeriod(): void
    {
        $accountId = new AccountId('account-test');
        $propertyId = new PropertyId('property-test');
        $wasteId = WasteId::generate();

        $containerRepository = ContainerRepositoryMother::inMemory();
        $container = ContainerMother::fromPropertyAccountAndWaste(
            (string) $propertyId,
            (string) $accountId,
            (string) $wasteId,
        );
        $containerRepository->add($container);

        $measurementRepository = MeasurementRepositoryMother::inMemory();
        $alertRepository = AlertRepositoryMother::inMemory();

        $config = AlertConfigurationMother::from(
            accountId: (string) $accountId,
            unusedContainerEnabled: true,
            unusedDays: 7,
        );

        $checker = new UnusedContainerAlertChecker($containerRepository, $measurementRepository, $alertRepository);
        $alerts = $checker->check($accountId, $config->enabledItemsForType($checker->type()), new \DateTimeImmutable('today'));

        $this->assertCount(1, $alerts);
        $this->assertNotNull($alerts[0]->propertyId());
        $this->assertNotNull($alerts[0]->wasteId());
        $this->assertTrue($propertyId->equals($alerts[0]->propertyId()));
        $this->assertTrue($wasteId->equals($alerts[0]->wasteId()));
    }

    public function testNoAlertWhenContainerHasRecentMeasurement(): void
    {
        $accountId = new AccountId('account-test');
        $propertyId = new PropertyId('property-test');
        $wasteId = WasteId::generate();
        $containerId = ContainerId::generate();

        $containerRepository = ContainerRepositoryMother::inMemory();
        $container = ContainerMother::fromPropertyAccountAndWasteAndId(
            (string) $propertyId,
            (string) $accountId,
            (string) $wasteId,
            (string) $containerId,
        );
        $containerRepository->add($container);

        $measurementRepository = MeasurementRepositoryMother::inMemory();
        $measurement = MeasurementMother::from(
            measurementId: MeasurementId::generate(),
            propertyId: $propertyId,
            wasteId: $wasteId,
            wasteManagerId: WasteManagerId::generate(),
            containerId: $containerId,
            registeredBy: UserId::generate(),
            accountId: $accountId,
            registeredAt: new \DateTimeImmutable('yesterday'),
        );
        $measurementRepository->add($measurement);

        $alertRepository = AlertRepositoryMother::inMemory();

        $config = AlertConfigurationMother::from(
            accountId: (string) $accountId,
            unusedContainerEnabled: true,
            unusedDays: 7,
        );

        $checker = new UnusedContainerAlertChecker($containerRepository, $measurementRepository, $alertRepository);
        $alerts = $checker->check($accountId, $config->enabledItemsForType($checker->type()), new \DateTimeImmutable('today'));

        $this->assertCount(0, $alerts);
    }

    public function testNoDuplicateAlertForSamePropertyAndWaste(): void
    {
        $accountId = new AccountId('account-test');
        $propertyId = new PropertyId('property-test');
        $wasteId = WasteId::generate();

        $containerRepository = ContainerRepositoryMother::inMemory();
        // Two containers with the same property+waste
        $container1 = ContainerMother::fromPropertyAccountAndWaste(
            (string) $propertyId,
            (string) $accountId,
            (string) $wasteId,
        );
        $container2 = ContainerMother::fromPropertyAccountAndWaste(
            (string) $propertyId,
            (string) $accountId,
            (string) $wasteId,
        );
        $containerRepository->add($container1);
        $containerRepository->add($container2);

        $measurementRepository = MeasurementRepositoryMother::inMemory();
        $alertRepository = AlertRepositoryMother::inMemory();

        $config = AlertConfigurationMother::from(
            accountId: (string) $accountId,
            unusedContainerEnabled: true,
            unusedDays: 7,
        );

        $checker = new UnusedContainerAlertChecker($containerRepository, $measurementRepository, $alertRepository);
        $alerts = $checker->check($accountId, $config->enabledItemsForType($checker->type()), new \DateTimeImmutable('today'));

        $this->assertCount(1, $alerts);
    }
}
