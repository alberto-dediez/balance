<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\BaseYearDeclaration\Remove;

use App\CarbonFootprint\Domain\Model\BaseYearDeclaration\BaseYearDeclaration;
use App\Shared\Application\CommandBus\CommandResponse;

class RemoveBaseYearDeclarationResponse implements CommandResponse
{
    public function __construct(
        public BaseYearDeclaration $declaration,
    ) {}
}
