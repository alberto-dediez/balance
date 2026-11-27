<?php

declare(strict_types=1);

namespace App\Property\Application\Property\ReleaseAccount;

use App\Property\Domain\Model\Property\PropertyId;
use App\Shared\Application\CommandBus\CommandResponse;

class ReleasePropertyAccountResponse implements CommandResponse
{
    public function __construct(public PropertyId $propertyId) {}
}
