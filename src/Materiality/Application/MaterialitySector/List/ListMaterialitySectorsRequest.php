<?php

declare(strict_types=1);

namespace App\Materiality\Application\MaterialitySector\List;

use App\Materiality\Domain\Model\MaterialitySector\MaterialitySectorFilter;
use App\Shared\Application\CommandBus\Command;

class ListMaterialitySectorsRequest extends MaterialitySectorFilter implements Command {}
