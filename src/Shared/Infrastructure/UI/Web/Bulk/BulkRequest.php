<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\UI\Web\Bulk;

use App\Shared\Application\CommandBus\Command;

class BulkRequest
{
    /** @var array<int, mixed> */
    public array $elements;

    public BulkAction $action;

    /** @return list<Command>  */
    public function commands(): array
    {
        return $this->action->commands($this);
    }
}
