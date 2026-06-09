<?php

declare(strict_types=1);

namespace App\DataExchange\Application\Exportation\Register;

use App\DataExchange\Domain\Model\Exportation\ExportationFormat;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Application\CommandBus\Command;

class RegisterExportationRequest implements Command
{
    /** @param string[] $columns */
    public function __construct(
        public string $type,
        public ExportationFormat $format,
        public array $columns,
        public object $filter,
        public ?AccountId $accountId = null,
    ) {}
}
