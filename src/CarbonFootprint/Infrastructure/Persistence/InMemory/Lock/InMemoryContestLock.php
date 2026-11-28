<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\InMemory\Lock;

use App\CarbonFootprint\Domain\Model\Contest\ContestId;
use App\CarbonFootprint\Domain\Model\Contest\ContestLock;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryContestLock implements ContestLock
{
    /** @var list<string> */
    private array $acquired = [];

    public function acquire(ContestId $contestId): void
    {
        $this->acquired[] = (string) $contestId;
    }

    /** @return list<string> */
    public function acquired(): array
    {
        return $this->acquired;
    }
}
