<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\InMemory\Lock;

use App\CarbonFootprint\Domain\Model\Contest\ContestPublicationLock;
use App\Security\Domain\Model\Account\AccountId;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryContestPublicationLock implements ContestPublicationLock
{
    /** @var list<string> */
    private array $acquired = [];

    public function acquire(AccountId $accountId): void
    {
        $this->acquired[] = (string) $accountId;
    }

    /** @return list<string> */
    public function acquired(): array
    {
        return $this->acquired;
    }
}
