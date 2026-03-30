<?php

declare(strict_types=1);

namespace App\Property\Application\Country\Update;

use App\Property\Domain\Model\Country\CountryId;
use App\Shared\Application\CommandBus\CommandResponse;

class UpdateCountryResponse implements CommandResponse
{
    public function __construct(public CountryId $countryId, public string $name, public ?string $iso) {}
}
