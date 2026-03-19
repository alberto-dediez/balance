<?php

declare(strict_types=1);

namespace App\Property\Application\Country\Update;

use App\Property\Domain\Model\Country\CountryId;
use App\Property\Domain\Model\Property\ExternalId;
use App\Shared\Application\CommandBus\Command;

class UpdateCountryRequest implements Command
{
    public function __construct(public CountryId $countryId, public string $name, public ?string $iso, public ?ExternalId $externalId = null) {}
}
