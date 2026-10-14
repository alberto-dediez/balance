<?php

declare(strict_types=1);

namespace App\Property\Application\Brand\Register;

use App\Property\Domain\Model\Property\ExternalId;
use App\Shared\Application\CommandBus\Command;

class RegisterBrandRequest implements Command
{
    public function __construct(public string $name = '', public ?ExternalId $externalId = null) {}
}
