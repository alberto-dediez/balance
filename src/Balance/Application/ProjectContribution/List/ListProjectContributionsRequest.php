<?php

declare(strict_types=1);

namespace App\Balance\Application\ProjectContribution\List;

use App\Balance\Domain\Model\ProjectContribution\ProjectContributionFilter;
use App\Property\Domain\Model\Property\PropertyId;

class ListProjectContributionsRequest extends ProjectContributionFilter
{
    public ?PropertyId $propertyId = null;
}
