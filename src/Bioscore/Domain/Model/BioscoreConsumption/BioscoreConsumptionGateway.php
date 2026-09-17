<?php

declare(strict_types=1);

namespace App\Bioscore\Domain\Model\BioscoreConsumption;

use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreUnavailableException;

interface BioscoreConsumptionGateway
{
    /**
     * @return list<BioscorePropertyConsumptions>
     *
     * @throws BioscoreUnavailableException
     */
    public function fetch(BioscoreConsumptionsQuery $query): array;
}
