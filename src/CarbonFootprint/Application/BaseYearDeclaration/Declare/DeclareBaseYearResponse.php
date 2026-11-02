<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\BaseYearDeclaration\Declare;

use App\CarbonFootprint\Domain\Model\BaseYearDeclaration\BaseYearDeclaration;
use App\Shared\Application\CommandBus\CommandResponse;

class DeclareBaseYearResponse implements CommandResponse
{
    public function __construct(
        public BaseYearDeclaration $declaration,
    ) {}
}
