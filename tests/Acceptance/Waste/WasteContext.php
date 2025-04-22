<?php

declare(strict_types=1);

namespace App\Tests\Acceptance\Waste;

use Behat\Step\Given;
use Behat\Behat\Context\Context;
use App\Security\Domain\Model\User\UserId;
use App\Waste\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Waste\Domain\Model\Container\ContainerId;
use App\Waste\Domain\Model\Waste\WasteRepository;
use App\Waste\Domain\Model\Measurement\MeasurementId;
use App\Waste\Domain\Model\Property\PropertyRepository;
use App\Tests\Unit\Waste\Domain\Model\Waste\WasteMother;
use App\Waste\Domain\Model\Container\ContainerRepository;
use App\Waste\Domain\Model\Measurement\MeasurementRepository;
use App\Tests\Unit\Waste\Domain\Model\Property\PropertyMother;
use App\Tests\Unit\Waste\Domain\Model\Container\ContainerMother;
use App\Tests\Unit\Waste\Domain\Model\Measurement\MeasurementMother;

final class WasteContext implements Context
{
    public function __construct(
        private WasteRepository $wasteRepository,
        private PropertyRepository $propertyRepository,
        private ContainerRepository $containerRepository,
        private MeasurementRepository $measurementRepository
    ) {
    }

    #[Given('waste :name with id :id exists')]
    public function wasteWithIdExists($name, $id): void
    {
        $waste = WasteMother::fromNameAndId($name, $id);
        
        $this->wasteRepository->add($waste);      
    }    

    #[Given('property :name with id :id exists')]
    public function propertyWithIdExists($name, $id): void
    {
        $property = PropertyMother::fromNameAndId($name, $id);
        
        $this->propertyRepository->add($property);      
    }    

    #[Given('property :name with id :id of account :account exists')]
    public function propertyWithIdOfAccountExists($name, $id, $account): void
    {
        $property = PropertyMother::fromNameAndAccountAndId($name, $account, $id);
        
        $this->propertyRepository->add($property);      
    }  

    #[Given('container for :waste in :propertyId with id :id exists')]
    public function containerForInWithIdExists($waste, $propertyId, $id): void
    {
        $property = $this->propertyRepository->find(new PropertyId($propertyId));

        $container = ContainerMother::fromPropertyAccountAndWasteAndId($propertyId, (string) $property->accountId(), $waste, $id);

        $this->containerRepository->add($container);
    }

    #[Given('measurement for container :container and account :account from user :user with id :id exists')]
    public function measurementForContainerAndAccountFromUserWithIdExists($containerId, $accountId, $user, $id): void
    {
        $container = $this->containerRepository->find(
            new ContainerId($containerId)
        );

        $measurement = MeasurementMother::from(
            containerId: $container->id(),
            propertyId: $container->propertyId(),
            wasteId: $container->wasteId(),
            purpose: $container->purpose(),
            measurementMethod: $container->measurementMethod(),
            registeredBy: new UserId($user),
            measurementId: new MeasurementId($id),
            accountId: new AccountId($accountId)
        );

        $this->measurementRepository->add($measurement);
    }
}
