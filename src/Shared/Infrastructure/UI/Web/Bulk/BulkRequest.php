<?php

namespace App\Shared\Infrastructure\UI\Web\Bulk;

class BulkRequest
{
    public array $elements;

    public BulkAction $action;

    public function commands(): array
    {
        return $this->action->commands($this);
    }
}