<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence\Doctrine\Repository;

use App\Shared\Domain\Model\CommandBus\ScheduledCommandRecord;
use App\Shared\Domain\Model\CommandBus\ScheduledCommandRecordRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.scheduledCommandRecords', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineScheduledCommandRecordRepository implements ScheduledCommandRecordRepository
{
    public function __construct(private readonly EntityManagerInterface $manager) {}

    public function findByCommandId(string $commandId): ?ScheduledCommandRecord
    {
        return $this->manager->find(ScheduledCommandRecord::class, $commandId);
    }

    public function save(ScheduledCommandRecord $record): void
    {
        $this->manager->persist($record);
        $this->manager->flush();
    }
}
