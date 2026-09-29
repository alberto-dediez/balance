<?php

declare(strict_types=1);

namespace App\Materiality\Application\MaterialitySector\Register;

use App\Materiality\Domain\Model\MaterialitySector\MaterialitySectorId;
use App\Shared\Application\CommandBus\CommandResponse;

class RegisterMaterialitySectorResponse implements CommandResponse
{
    public function __construct(
        public MaterialitySectorId $materialitySectorId,
        public string $name,
    ) {}
}
