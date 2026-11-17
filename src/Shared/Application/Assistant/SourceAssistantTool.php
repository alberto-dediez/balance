<?php

declare(strict_types=1);

namespace App\Shared\Application\Assistant;

interface SourceAssistantTool extends AssistantTool
{
    /** @param array<string, list<string>> $arguments */
    public function sourceHref(array $arguments): ?string;
}
