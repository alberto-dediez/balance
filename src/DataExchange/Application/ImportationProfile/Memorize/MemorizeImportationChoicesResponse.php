<?php

declare(strict_types=1);

namespace App\DataExchange\Application\ImportationProfile\Memorize;

use App\DataExchange\Domain\Model\ImportationProfile\ImportationProfileId;
use App\Shared\Application\CommandBus\CommandResponse;

class MemorizeImportationChoicesResponse implements CommandResponse
{
    public function __construct(
        public ImportationProfileId $importationProfileId,
    ) {}
}
