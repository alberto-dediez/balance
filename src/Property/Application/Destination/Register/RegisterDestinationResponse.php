<?php

declare(strict_types=1);

namespace App\Property\Application\Destination\Register;

use App\Property\Domain\Model\Country\CountryId;
use App\Property\Domain\Model\Destination\DestinationId;
use App\Shared\Application\CommandBus\CommandResponse;

class RegisterDestinationResponse implements CommandResponse
{
    public function __construct(public DestinationId $destinationId, public string $name, public CountryId $countryId) {}
}
