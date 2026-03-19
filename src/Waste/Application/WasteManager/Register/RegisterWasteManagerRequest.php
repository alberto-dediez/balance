<?php

declare(strict_types=1);

namespace App\Waste\Application\WasteManager\Register;

use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Application\CommandBus\Command;

class RegisterWasteManagerRequest implements Command
{
    public function __construct(public string $name = '', public bool $isPublic = true, public ?AccountId $accountId = null) {}
}
