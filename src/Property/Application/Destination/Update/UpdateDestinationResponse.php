<?php

declare(strict_types=1);

namespace App\Property\Application\Destination\Update;

use App\Property\Domain\Model\Country\CountryId;
use App\Property\Domain\Model\Destination\DestinationId;
use App\Shared\Application\CommandBus\CommandResponse;

class UpdateDestinationResponse implements CommandResponse
{
    public function __construct(public DestinationId $destinationId, public string $name, public CountryId $countryId) {}
}
