<?php

declare(strict_types=1);

namespace App\Tests\Acceptance\Waste;

use Behat\Step\Given;
use Behat\Behat\Context\Context;
use App\Waste\Domain\Model\Waste\WasteRepository;
use App\Tests\Unit\Waste\Domain\Model\Waste\WasteMother;

final class WasteContext implements Context
{
    public function __construct(
        private WasteRepository $wasteRepository
    ) {
    }

    #[Given('waste :name with id :id exists')]
    public function wasteWithIdExists($name, $id): void
    {
        $waste = WasteMother::fromNameAndId($name, $id);
        
        $this->wasteRepository->add($waste);      
    }    

}
