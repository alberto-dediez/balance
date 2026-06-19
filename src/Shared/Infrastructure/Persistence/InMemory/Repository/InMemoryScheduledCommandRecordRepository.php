<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence\InMemory\Repository;

use App\Shared\Domain\Model\CommandBus\ScheduledCommandRecord;
use App\Shared\Domain\Model\CommandBus\ScheduledCommandRecordRepository;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryScheduledCommandRecordRepository implements ScheduledCommandRecordRepository
{
    /** @var array<string, ScheduledCommandRecord> */
    private array $records = [];

    public function findByCommandId(string $commandId): ?ScheduledCommandRecord
    {
        return $this->records[$commandId] ?? null;
    }

    public function save(ScheduledCommandRecord $record): void
    {
        $this->records[$record->commandId()] = $record;
    }
}
