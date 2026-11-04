<?php

declare(strict_types=1);

namespace App\Tests\Unit\DataExchange\Application\Importation\Execute;

use App\Shared\Application\CommandBus\Command;

class FakeImportRequest implements Command
{
    public function __construct(
        public ?string $containerId,
        public ?string $propertyId,
        public ?string $wasteId,
        public ?string $purpose,
        public ?string $wasteManagerId,
        public ?string $quantity,
        public ?string $load,
        public ?string $justification,
        public ?string $unit,
        public ?string $day,
        public ?string $reference,
    ) {}
}
