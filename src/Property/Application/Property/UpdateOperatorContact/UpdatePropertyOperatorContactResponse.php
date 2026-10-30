<?php

declare(strict_types=1);

namespace App\Property\Application\Property\UpdateOperatorContact;

use App\Property\Domain\Model\Property\OperatorContact;
use App\Property\Domain\Model\Property\PropertyId;
use App\Shared\Application\CommandBus\CommandResponse;

class UpdatePropertyOperatorContactResponse implements CommandResponse
{
    public function __construct(public PropertyId $propertyId, public OperatorContact $operatorContact) {}
}
