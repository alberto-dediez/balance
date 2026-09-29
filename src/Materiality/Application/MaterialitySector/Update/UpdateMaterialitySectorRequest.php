<?php

declare(strict_types=1);

namespace App\Materiality\Application\MaterialitySector\Update;

use App\Materiality\Domain\Model\MaterialitySector\MaterialitySectorId;
use App\Shared\Application\CommandBus\Command;

class UpdateMaterialitySectorRequest implements Command
{
    public function __construct(
        public MaterialitySectorId $materialitySectorId,
        public string $name,
    ) {}
}
