<?php

declare(strict_types=1);

namespace App\Property\Application\Property\Match;

use App\Property\Domain\Model\Property\ExternalId;
use App\Property\Domain\Model\Property\ExternalSource;
use App\Property\Domain\Model\Property\PropertyInfo;

class MatchPropertyRequest
{
    public function __construct(public string $name, public PropertyInfo $info, public ExternalId $externalId, public ExternalSource $source) {}
}
