<?php

declare(strict_types=1);

namespace App\DataExchange\Application\ImportationProfile\Recall;

use App\DataExchange\Domain\Model\Importation\ImportationColumnConfiguration;
use App\Shared\Application\CommandBus\CommandResponse;

class RecallImportationChoicesResponse implements CommandResponse
{
    public function __construct(
        /** @var ImportationColumnConfiguration[] $configurations */
        public array $configurations,
        /** @var array<string, mixed> $defaultValues */
        public array $defaultValues,
    ) {}
}
