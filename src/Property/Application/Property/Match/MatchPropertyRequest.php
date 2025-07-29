<?php

namespace App\Property\Application\Property\Match;

use App\Property\Domain\Model\Property\ExternalId;
use App\Property\Domain\Model\Property\PropertyInfo;
use App\Property\Domain\Model\Property\ExternalSource;

class MatchPropertyRequest
{
    public function __construct(public string $name, public PropertyInfo $info, public ExternalId $externalId, public ExternalSource $source)
    {

    }
}