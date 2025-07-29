<?php

namespace App\Property\Application\Property\Match;

use App\Property\Domain\Model\Property\PropertyId;

class MatchPropertyResponse
{
    public function __construct(public ?PropertyId $propertyId)
    {
        
    }
}