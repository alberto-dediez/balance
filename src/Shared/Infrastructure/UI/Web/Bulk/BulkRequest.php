<?php

namespace App\Shared\Infrastructure\UI\Web\Bulk;

use App\Shared\Application\CommandBus\Command;

class BulkRequest
{
    /** @var array<int, mixed> $elements  */
    public array $elements;

    public BulkAction $action;

    /** @return list<Command>  */
    public function commands(): array
    {
        return $this->action->commands($this);
    }
}
