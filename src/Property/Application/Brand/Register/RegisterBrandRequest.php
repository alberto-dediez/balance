<?php

namespace App\Property\Application\Brand\Register;

use App\Shared\Application\CommandBus\Command;
use App\Property\Domain\Model\Property\ExternalId;

class RegisterBrandRequest implements Command
{
    public function __construct(public string $name = '', public ?ExternalId $externalId = null)
    {
        
    }
}