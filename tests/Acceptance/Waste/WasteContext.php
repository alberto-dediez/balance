<?php

declare(strict_types=1);

namespace App\Tests\Acceptance\Waste;

use Behat\Step\Given;
use Behat\Behat\Context\Context;
use App\Security\Domain\Model\User\UserId;
use App\Security\Domain\Model\Account\AccountId;
use App\Tests\Acceptance\Shared\AcceptanceClient;
use App\Waste\Domain\Model\Container\ContainerId;
use App\Waste\Domain\Model\Waste\WasteRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use App\Property\Domain\Model\Property\PropertyId;
use App\Waste\Domain\Model\Measurement\MeasurementId;
use App\Waste\Domain\Model\WasteManager\WasteManagerId;
use App\Tests\Unit\Waste\Domain\Model\Waste\WasteMother;
use App\Waste\Domain\Model\Container\ContainerRepository;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Waste\Domain\Model\Measurement\MeasurementRepository;
use App\Waste\Domain\Model\WasteManager\WasteManagerRepository;
use App\Tests\Unit\Waste\Domain\Model\Container\ContainerMother;
use App\Tests\Unit\Waste\Domain\Model\Measurement\MeasurementMother;
use App\Tests\Unit\Waste\Domain\Model\WasteManager\WasteManagerMother;

final class WasteContext implements Context
{
    private AcceptanceClient $client;

    public function __construct(
        KernelBrowser $kernelBrowser
    ) {
        $this->client = AcceptanceClient::instance($kernelBrowser);
    }

    public function getWasteRepository() : WasteRepository
    {
        return $this->client->getContainer()->get(WasteRepository::class);
    }

    public function getContainerRepository() : ContainerRepository
    {
        return $this->client->getContainer()->get(ContainerRepository::class);
    }

    public function getPropertyRepository() : PropertyRepository
    {
        return $this->client->getContainer()->get(PropertyRepository::class);
    }

    public function getMeasurementRepository() : MeasurementRepository
    {
        return $this->client->getContainer()->get(MeasurementRepository::class);
    }

    public function getWasteManagerRepository() : WasteManagerRepository
    {
        return $this->client->getContainer()->get(WasteManagerRepository::class);
    }    

    #[Given('waste :name with id :id exists')]
    public function wasteWithIdExists($name, $id): void
    {
        $waste = WasteMother::fromNameAndId($name, $id);
        
        $this->getWasteRepository()->add($waste);      
    }    

    #[Given('container for :waste in :propertyId with id :id exists')]
    public function containerForInWithIdExists($waste, $propertyId, $id): void
    {
        $property = $this->getPropertyRepository()->find(new PropertyId($propertyId));

        $container = ContainerMother::fromPropertyAccountAndWasteAndId($propertyId, (string) $property->accountId(), $waste, $id);

        $this->getContainerRepository()->add($container);
    }

    #[Given('container for :waste in :propertyId and managed by :manager with id :id exists')]
    public function containerForInAndManagedByWithIdExists($waste, $propertyId, $managerId, $id): void
    {
        $property = $this->getPropertyRepository()->find(new PropertyId($propertyId));

        $container = ContainerMother::fromPropertyAccountAndWasteAndIdAndManager($propertyId, (string) $property->accountId(), $waste, $id, $managerId);

        $this->getContainerRepository()->add($container);
    }

    #[Given('measurement for container :container and account :account from user :user with id :id exists')]
    public function measurementForContainerAndAccountFromUserWithIdExists($containerId, $accountId, $user, $id): void
    {
        $container = $this->getContainerRepository()->find(
            new ContainerId($containerId)
        );

        $measurement = MeasurementMother::from(
            containerId: $container->id(),
            propertyId: $container->propertyId(),
            wasteId: $container->wasteId(),
            wasteManagerId: $container->wasteManagerId(),
            purpose: $container->purpose(),
            measurementMethod: $container->measurementMethod(),
            registeredBy: new UserId($user),
            measurementId: new MeasurementId($id),
            accountId: new AccountId($accountId)
        );

        $this->getMeasurementRepository()->add($measurement);
    }

    #[Given('waste manager :name with id :wasteManagerId of account :accountId exists')]
    public function wasteManagerWithIdOfAccountExists($name, $wasteManagerId, $accountId): void
    {
        $wasteManager = WasteManagerMother::fromNameAndIdAndAccount(
            name: $name,
            wasteManagerId: new WasteManagerId($wasteManagerId),
            accountId: new AccountId($accountId)
        );

        $this->getWasteManagerRepository()->add($wasteManager);
    }
}
