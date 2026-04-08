<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\DoggyBagSign\Update;

use App\FoodLoss\Domain\Model\DoggyBagSign\DoggyBagSignId;
use App\Property\Domain\Model\Property\PropertyId;
use App\Shared\Application\CommandBus\Command;

class UpdateDoggyBagSignRequest implements Command
{
    public function __construct(
        public DoggyBagSignId $doggyBagSignId,
        public string $name = '',
        public string $location = '',
        public ?PropertyId $propertyId = null,
        public ?string $temporaryFilePath = null,
        public ?string $fileExtension = null,
    ) {}
}
