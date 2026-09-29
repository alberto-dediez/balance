<?php

declare(strict_types=1);

namespace App\Materiality\Application\MaterialitySector\Update;

use App\Materiality\Domain\Model\MaterialitySector\MaterialitySectorId;
use App\Shared\Application\CommandBus\CommandResponse;

class UpdateMaterialitySectorResponse implements CommandResponse
{
    public function __construct(
        public MaterialitySectorId $materialitySectorId,
        public string $name,
    ) {}
}
