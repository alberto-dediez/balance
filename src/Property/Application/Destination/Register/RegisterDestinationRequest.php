<?php

declare(strict_types=1);

namespace App\Property\Application\Destination\Register;

use App\Property\Domain\Model\Country\CountryId;
use App\Property\Domain\Model\Property\ExternalId;
use App\Shared\Application\CommandBus\Command;

class RegisterDestinationRequest implements Command
{
    public function __construct(public string $name, public CountryId $countryId, public ?ExternalId $externalId = null) {}
}
