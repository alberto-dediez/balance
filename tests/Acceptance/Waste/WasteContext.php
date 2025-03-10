<?php

declare(strict_types=1);

namespace App\Tests\Acceptance\Waste;

use Behat\Step\Given;
use Behat\Behat\Context\Context;
use App\Waste\Domain\Model\Waste\WasteRepository;
use App\Waste\Domain\Model\Property\PropertyRepository;
use App\Tests\Unit\Waste\Domain\Model\Waste\WasteMother;
use App\Tests\Unit\Waste\Domain\Model\Property\PropertyMother;

final class WasteContext implements Context
{
    public function __construct(
        private WasteRepository $wasteRepository,
        private PropertyRepository $propertyRepository
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
}
