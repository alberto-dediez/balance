<?php

declare(strict_types=1);

namespace App\Tests\Unit\Waste\Application\Alert\Check\Checker;

use App\Security\Domain\Model\Account\AccountId;
use App\Tests\Unit\Waste\Domain\Model\Container\ContainerMother;
use App\Tests\Unit\Waste\Domain\Model\Container\ContainerRepositoryMother;
use App\Tests\Unit\Waste\Domain\Model\Measurement\MeasurementMother;
use App\Tests\Unit\Waste\Domain\Model\Measurement\MeasurementRepositoryMother;
use App\Waste\Application\Alert\Check\Checker\UnusedContainerAlertChecker;
use App\Waste\Domain\Model\Alert\AlertPriority;
use App\Waste\Domain\Model\Alert\AlertType;
use App\Waste\Domain\Model\Alert\ExistingAlertsIndex;
use App\Waste\Domain\Model\AlertConfiguration\AlertConfigurationItem;
use App\Waste\Domain\Model\AlertConfiguration\AlertConfigurationItemId;
use App\Waste\Domain\Model\Measurement\MeasurementId;
use App\Waste\Domain\Model\WasteManager\WasteManagerId;
use PHPUnit\Framework\TestCase;

class UnusedContainerAlertCheckerTest extends TestCase
{
    public function testTypeReturnsUnusedContainer(): void
    {
        $sut = $this->createSUT();

        $this->assertEquals(AlertType::UnusedContainer, $sut->type());
    }

    public function testReturnsNoAlertsWhenContainersHaveRecentMeasurements(): void
    {
        $containerRepository = ContainerRepositoryMother::inMemory();
        $measurementRepository = MeasurementRepositoryMother::inMemory();

        $accountId = new AccountId('account-id-test');

        $container = ContainerMother::fromPropertyAccountAndWaste('property-id-test', 'account-id-test', 'waste-id-test');
        $containerRepository->add($container);

        $checkDate = new \DateTimeImmutable('2026-06-01');
        $this->assertNotNull($container->propertyId());
        $this->assertNotNull($container->wasteId());
        $recentMeasurement = MeasurementMother::from(
            measurementId: MeasurementId::generate(),
            propertyId: $container->propertyId(),
            wasteId: $container->wasteId(),
            wasteManagerId: WasteManagerId::generate(),
            containerId: $container->id(),
            registeredBy: \App\Security\Domain\Model\User\UserId::generate(),
            accountId: $accountId,
            registeredAt: $checkDate->modify('-3 days'),
        );
        $measurementRepository->add($recentMeasurement);

        $sut = new UnusedContainerAlertChecker($containerRepository, $measurementRepository);

        $alerts = $sut->check($accountId, [$this->createRule()], $checkDate, new ExistingAlertsIndex());

        $this->assertCount(0, $alerts);
    }

    public function testDetectsAlertForContainerWithNoRecentMeasurements(): void
    {
        $containerRepository = ContainerRepositoryMother::inMemory();
        $measurementRepository = MeasurementRepositoryMother::inMemory();

        $accountId = new AccountId('account-id-test');

        $container = ContainerMother::fromPropertyAccountAndWaste('property-id-test', 'account-id-test', 'waste-id-test');
        $containerRepository->add($container);

        $sut = new UnusedContainerAlertChecker($containerRepository, $measurementRepository);

        $checkDate = new \DateTimeImmutable('2026-06-01');
        $alerts = $sut->check($accountId, [$this->createRule()], $checkDate, new ExistingAlertsIndex());

        $this->assertCount(1, $alerts);
        $this->assertEquals(AlertType::UnusedContainer, $alerts[0]->type());
        $this->assertEquals('account-id-test', (string) $alerts[0]->accountId());
    }

    public function testSkipsAlertIfAlreadyInExistingAlertsIndex(): void
    {
        $containerRepository = ContainerRepositoryMother::inMemory();
        $measurementRepository = MeasurementRepositoryMother::inMemory();

        $accountId = new AccountId('account-id-test');

        $container = ContainerMother::fromPropertyAccountAndWaste('property-id-test', 'account-id-test', 'waste-id-test');
        $containerRepository->add($container);

        $sut = new UnusedContainerAlertChecker($containerRepository, $measurementRepository);

        $checkDate = new \DateTimeImmutable('2026-06-01');

        $existingAlerts = new ExistingAlertsIndex();
        $existingAlerts->add(
            type: AlertType::UnusedContainer,
            propertyId: $container->propertyId(),
            wasteId: $container->wasteId(),
            alertPriority: AlertPriority::Warning,
        );

        $alerts = $sut->check($accountId, [$this->createRule()], $checkDate, $existingAlerts);

        $this->assertCount(0, $alerts);
    }

    public function testReturnsNoAlertsWhenNoContainersForAccount(): void
    {
        $containerRepository = ContainerRepositoryMother::inMemory();
        $measurementRepository = MeasurementRepositoryMother::inMemory();

        $accountId = new AccountId('account-id-test');

        $sut = new UnusedContainerAlertChecker($containerRepository, $measurementRepository);

        $checkDate = new \DateTimeImmutable('2026-06-01');
        $alerts = $sut->check($accountId, [$this->createRule()], $checkDate, new ExistingAlertsIndex());

        $this->assertCount(0, $alerts);
    }

    private function createSUT(): UnusedContainerAlertChecker
    {
        return new UnusedContainerAlertChecker(
            containerRepository: ContainerRepositoryMother::inMemory(),
            measurementRepository: MeasurementRepositoryMother::inMemory(),
        );
    }

    private function createRule(): AlertConfigurationItem
    {
        return new AlertConfigurationItem(
            id: AlertConfigurationItemId::generate(),
            type: AlertType::UnusedContainer,
            enabled: true,
            parameters: ['unusedDays' => 7],
            priority: AlertPriority::Warning,
        );
    }
}
