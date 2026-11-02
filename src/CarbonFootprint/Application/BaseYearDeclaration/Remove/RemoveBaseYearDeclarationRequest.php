<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\BaseYearDeclaration\Remove;

use App\CarbonFootprint\Domain\Model\BaseYearDeclaration\BaseYearDeclarationId;
use App\Shared\Application\CommandBus\Command;

class RemoveBaseYearDeclarationRequest implements Command
{
    public function __construct(
        public BaseYearDeclarationId $baseYearDeclarationId,
    ) {}
}
