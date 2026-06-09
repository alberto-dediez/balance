<?php

declare(strict_types=1);

namespace App\Waste\Infrastructure\Persistence\InMemory\Repository;

use App\Security\Domain\Model\Account\AccountId;
use App\Waste\Domain\Model\AlertCheckQueue\AlertCheckQueue;
use App\Waste\Domain\Model\AlertCheckQueue\AlertCheckQueueId;
use App\Waste\Domain\Model\AlertCheckQueue\AlertCheckQueueRepository;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: AlertCheckQueueRepository::class, public: true)]
#[When(env: 'test')]
class InMemoryAlertCheckQueueRepository implements AlertCheckQueueRepository
{
    /** @param array<string, AlertCheckQueue> $entries */
    public function __construct(private array $entries = []) {}

    public function add(AlertCheckQueue $entry): void
    {
        $this->entries[(string) $entry->id()] = $entry;
    }

    public function find(AlertCheckQueueId $id): ?AlertCheckQueue
    {
        return $this->entries[(string) $id] ?? null;
    }

    public function existsFor(AccountId $accountId, \DateTimeImmutable $checkDate): bool
    {
        foreach ($this->entries as $entry) {
            if (
                null !== $entry->accountId()
                && $entry->accountId()->equals($accountId)
                && $entry->checkDate()->format('Y-m-d') === $checkDate->format('Y-m-d')
            ) {
                return true;
            }
        }

        return false;
    }

    /** @return array<AlertCheckQueue> */
    public function findAll(): array
    {
        return array_values($this->entries);
    }

    public function remove(AlertCheckQueue $entry): void
    {
        unset($this->entries[(string) $entry->id()]);
    }
}
