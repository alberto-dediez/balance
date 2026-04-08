<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\DoggyBagSign\Register;

use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Application\CommandBus\Command;

class RegisterDoggyBagSignRequest implements Command
{
    public function __construct(
        public string $name = '',
        public string $location = '',
        public ?PropertyId $propertyId = null,
        public ?AccountId $accountId = null,
        public string $temporaryFilePath = '',
        public string $fileExtension = '',
    ) {}
}
