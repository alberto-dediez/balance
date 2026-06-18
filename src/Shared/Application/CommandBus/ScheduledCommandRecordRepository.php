<?php

declare(strict_types=1);

namespace App\Shared\Application\CommandBus;

interface ScheduledCommandRecordRepository
{
    public function findByCommandId(string $commandId): ?ScheduledCommandRecord;

    public function save(ScheduledCommandRecord $record): void;
}
