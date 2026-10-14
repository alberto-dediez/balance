<?php

declare(strict_types=1);

namespace App\Property\Domain\Model\Company;

use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\AggregateRootIdTrait;

class CompanyId implements AggregateRootId
{
    use AggregateRootIdTrait;
}
