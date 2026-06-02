<?php

declare(strict_types=1);

namespace App\Tests\Acceptance\Waste;

use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\UserId;
use App\Tests\Acceptance\Shared\AcceptanceClient;
use App\Tests\Unit\Waste\Domain\Model\Alert\AlertMother;
use App\Tests\Unit\Waste\Domain\Model\AlertConfiguration\AlertConfigurationMother;
use App\Tests\Unit\Waste\Domain\Model\Container\ContainerMother;
use App\Tests\Unit\Waste\Domain\Model\DailyWaste\DailyWasteMother;
use App\Tests\Unit\Waste\Domain\Model\Measurement\MeasurementMother;
use App\Tests\Unit\Waste\Domain\Model\Waste\WasteMother;
use App\Tests\Unit\Waste\Domain\Model\WasteManager\WasteManagerMother;
use App\Waste\Domain\Model\Alert\AlertRepository;
use App\Waste\Domain\Model\AlertConfiguration\AlertConfigurationRepository;
use App\Waste\Domain\Model\Container\ContainerId;
use App\Waste\Domain\Model\Container\ContainerRepository;
use App\Waste\Domain\Model\DailyWaste\DailyWasteRepository;
use App\Waste\Domain\Model\Measurement\MeasurementId;
use App\Waste\Domain\Model\Measurement\MeasurementRepository;
use App\Waste\Domain\Model\Waste\WasteRepository;
use App\Waste\Domain\Model\WasteManager\WasteManagerId;
use App\Waste\Domain\Model\WasteManager\WasteManagerRepository;
use Behat\Behat\Context\Context;
use Behat\Step\Given;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

final readonly class WasteContext implements Context
{
    private AcceptanceClient $client;

    public function __construct(
        KernelBrowser $kernelBrowser,
    ) {
        $this->client = AcceptanceClient::instance($kernelBrowser);
    }

    public function getWasteRepository(): WasteRepository
    {
        return $this->client->getContainer()->get(WasteRepository::class);
    }

    public function getContainerRepository(): ContainerRepository
    {
        return $this->client->getContainer()->get(ContainerRepository::class);
    }

    public function getPropertyRepository(): PropertyRepository
    {
        return $this->client->getContainer()->get(PropertyRepository::class);
    }

    public function getMeasurementRepository(): MeasurementRepository
    {
        return $this->client->getContainer()->get(MeasurementRepository::class);
    }

    public function getWasteManagerRepository(): WasteManagerRepository
    {
        return $this->client->getContainer()->get(WasteManagerRepository::class);
    }

    public function getDailyWasteRepository(): DailyWasteRepository
    {
        return $this->client->getContainer()->get(DailyWasteRepository::class);
    }

    public function getAlertRepository(): AlertRepository
    {
        return $this->client->getContainer()->get(AlertRepository::class);
    }

    public function getAlertConfigurationRepository(): AlertConfigurationRepository
    {
        return $this->client->getContainer()->get(AlertConfigurationRepository::class);
    }

    #[Given('waste :name with id :id exists')]
    public function wasteWithIdExists(string $name, string $id): void
    {
        $waste = WasteMother::fromNameAndId($name, $id);

        $this->getWasteRepository()->add($waste);
    }

    #[Given('container for :waste in :propertyId with id :id exists')]
    public function containerForInWithIdExists(string $waste, string $propertyId, string $id): void
    {
        $property = $this->getPropertyRepository()->find(new PropertyId($propertyId));
        assert(null !== $property);

        $container = ContainerMother::fromPropertyAccountAndWasteAndId($propertyId, (string) $property->accountId(), $waste, $id);

        $this->getContainerRepository()->add($container);
    }

    #[Given('container for :waste in :propertyId and managed by :manager with id :id exists')]
    public function containerForInAndManagedByWithIdExists(string $waste, string $propertyId, string $managerId, string $id): void
    {
        $property = $this->getPropertyRepository()->find(new PropertyId($propertyId));
        assert(null !== $property);

        $container = ContainerMother::fromPropertyAccountAndWasteAndIdAndManager($propertyId, (string) $property->accountId(), $waste, $id, $managerId);

        $this->getContainerRepository()->add($container);
    }

    #[Given('measurement for container :container and account :account from user :user with id :id exists')]
    public function measurementForContainerAndAccountFromUserWithIdExists(string $containerId, string $accountId, string $user, string $id): void
    {
        $container = $this->getContainerRepository()->find(
            new ContainerId($containerId),
        );
        assert(null !== $container);

        $propertyId = $container->propertyId();
        $wasteId = $container->wasteId();
        $wasteManagerId = $container->wasteManagerId();
        assert(null !== $propertyId);
        assert(null !== $wasteId);
        assert(null !== $wasteManagerId);

        $measurement = MeasurementMother::from(
            measurementId: new MeasurementId($id),
            propertyId: $propertyId,
            wasteId: $wasteId,
            wasteManagerId: $wasteManagerId,
            containerId: $container->id(),
            registeredBy: new UserId($user),
            accountId: new AccountId($accountId),
            purpose: $container->purpose(),
            measurementMethod: $container->measurementMethod(),
        );

        $this->getMeasurementRepository()->add($measurement);
    }

    #[Given('waste manager :name with id :wasteManagerId of account :accountId exists')]
    public function wasteManagerWithIdOfAccountExists(string $name, string $wasteManagerId, string $accountId): void
    {
        $wasteManager = WasteManagerMother::fromNameAndIdAndAccount(
            name: $name,
            wasteManagerId: new WasteManagerId($wasteManagerId),
            accountId: new AccountId($accountId),
        );

        $this->getWasteManagerRepository()->add($wasteManager);
    }

    #[Given('daily waste for day :day with id :dailyWasteId of account :accountId exists')]
    public function dailyWasteForDayWithIdOfAccountExists(string $day, string $dailyWasteId, string $accountId): void
    {
        $dailyWaste = DailyWasteMother::fromDayAndIdAndAccount($day, $dailyWasteId, $accountId);

        $this->getDailyWasteRepository()->add($dailyWaste);
    }

    #[Given('alert with id :id for account :accountId exists')]
    public function alertWithIdForAccountExists(string $id, string $accountId): void
    {
        $alert = AlertMother::fromIdAndAccount($id, $accountId);

        $this->getAlertRepository()->add($alert);
    }

    #[Given('alert configuration with id :id for account :accountId exists')]
    public function alertConfigurationWithIdForAccountExists(string $id, string $accountId): void
    {
        $config = AlertConfigurationMother::fromIdAndAccount($id, $accountId);

        $this->getAlertConfigurationRepository()->add($config);
    }
}
