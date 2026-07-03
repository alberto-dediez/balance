<?php

declare(strict_types=1);

namespace App\Tests\Unit\DataExchange\Infrastructure\Persistence\InMemory\Repository;

use App\DataExchange\Infrastructure\Persistence\InMemory\Repository\InMemoryImportationProfileRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Tests\Unit\DataExchange\Domain\Model\ImportationProfile\ImportationProfileMother;
use PHPUnit\Framework\TestCase;

class InMemoryImportationProfileRepositoryTest extends TestCase
{
    public function testFindByTypeAndAccountWithAccount(): void
    {
        $accountId = AccountId::generate();
        $profile = ImportationProfileMother::fromTypeAndAccount('measurements', $accountId);

        $repository = new InMemoryImportationProfileRepository();
        $repository->add($profile);

        $found = $repository->findByTypeAndAccount('measurements', $accountId);

        $this->assertNotNull($found);
        $this->assertTrue($found->id()->equals($profile->id()));

        $this->assertNull($repository->findByTypeAndAccount('measurements', AccountId::generate()));
        $this->assertNull($repository->findByTypeAndAccount('other-type', $accountId));
    }

    public function testFindByTypeAndAccountWithGlobalMemory(): void
    {
        $profile = ImportationProfileMother::fromTypeAndAccount('measurements', null);

        $repository = new InMemoryImportationProfileRepository();
        $repository->add($profile);

        $found = $repository->findByTypeAndAccount('measurements', null);

        $this->assertNotNull($found);
        $this->assertTrue($found->id()->equals($profile->id()));

        $this->assertNull($repository->findByTypeAndAccount('measurements', AccountId::generate()));
    }

    public function testRemove(): void
    {
        $profile = ImportationProfileMother::random();

        $repository = new InMemoryImportationProfileRepository();
        $repository->add($profile);

        $repository->remove($profile);

        $this->assertNull($repository->find($profile->id()));
    }
}
