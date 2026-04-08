<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\DoggyBagException\Register;

use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Application\CommandBus\Command;

class RegisterDoggyBagExceptionRequest implements Command
{
    public function __construct(
        public string $description = '',
        public ?CriticalPointId $criticalPointId = null,
        public ?AccountId $accountId = null,
    ) {}
}
