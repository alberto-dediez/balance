<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\WasteManager\Register;

use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Application\CommandBus\Command;

class RegisterWasteManagerRequest implements Command
{
    public function __construct(
        public string $name = '',
        public bool $animalFeedAuthorized = false,
        public bool $compostAuthorized = false,
        public ?AccountId $accountId = null,
    ) {}
}
