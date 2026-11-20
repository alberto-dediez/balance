<?php

declare(strict_types=1);

namespace App\Tests\Unit\Library\Domain\Model\Folder;

use App\Library\Domain\Model\Folder\Folder;
use App\Library\Domain\Model\Folder\FolderFilter;
use App\Library\Domain\Model\Folder\FolderRepository;
use App\Security\Domain\Model\Account\AccountId;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class FolderRepositoryTest extends KernelTestCase
{
    public function testAddFolderToRepository(): void
    {
        $repository = $this->createSUT();

        $folder = FolderMother::fromName('Contratos');

        $this->assertNull($repository->find($folder->id()));

        $repository->add($folder);

        $recovered = $repository->find($folder->id());

        $this->assertNotNull($recovered);
        $this->assertEquals($folder->id(), $recovered->id());
        $this->assertEquals('Contratos', $recovered->name());
    }

    public function testRemoveFolderFromRepository(): void
    {
        $repository = $this->createSUT();

        $folder = FolderMother::fromName('Contratos');

        $repository->add($folder);

        $this->assertNotNull($repository->find($folder->id()));

        $repository->remove($folder);

        $this->assertNull($repository->find($folder->id()));
    }

    public function testFindByFilterByPartialName(): void
    {
        $repository = $this->createSUT();

        $repository->add(FolderMother::fromName('Contratos'));
        $repository->add(FolderMother::fromName('Facturas'));
        $repository->add(FolderMother::fromName('Contratos firmados'));

        $this->assertCount(3, $repository->findByFilter(new FolderFilter()));
        $this->assertCount(2, $repository->findByFilter(new FolderFilter(name: 'contr')));
    }

    public function testFindByFilterByAccount(): void
    {
        $repository = $this->createSUT();

        $accountId = AccountId::generate();

        $repository->add(FolderMother::fromNameAndAccount('Propia', $accountId));
        $repository->add(FolderMother::fromNameAndAccount('Ajena', AccountId::generate()));
        $repository->add(FolderMother::fromName('Sin cuenta'));

        $result = $repository->findByFilter(new FolderFilter(accountId: $accountId));

        $this->assertSame(['Propia'], $this->namesOf($result));
    }

    public function testFindByFilterWithoutAccount(): void
    {
        $repository = $this->createSUT();

        $repository->add(FolderMother::fromNameAndAccount('Propia', AccountId::generate()));
        $repository->add(FolderMother::fromName('Sin cuenta'));

        $result = $repository->findByFilter(new FolderFilter(withoutAccount: true));

        $this->assertSame(['Sin cuenta'], $this->namesOf($result));
    }

    public function testFindByFilterOrdersByName(): void
    {
        $repository = $this->createSUT();

        $repository->add(FolderMother::fromName('Bravo'));
        $repository->add(FolderMother::fromName('Charlie'));
        $repository->add(FolderMother::fromName('Alfa'));

        $this->assertSame(['Alfa', 'Bravo', 'Charlie'], $this->namesOf($repository->findByFilter(new FolderFilter())));

        $filter = new FolderFilter(limit: 2);
        $filter->orderWay = 'DESC';

        $this->assertSame(['Charlie', 'Bravo'], $this->namesOf($repository->findByFilter($filter)));

        $filter->page = 1;

        $this->assertSame(['Alfa'], $this->namesOf($repository->findByFilter($filter)));
    }

    public function testTotalResultsIgnoresPagination(): void
    {
        $repository = $this->createSUT();

        $repository->add(FolderMother::fromName('Alfa'));
        $repository->add(FolderMother::fromName('Bravo'));
        $repository->add(FolderMother::fromName('Charlie'));

        $filter = new FolderFilter(limit: 2);

        $this->assertCount(2, $repository->findByFilter($filter));
        $this->assertSame(3, $repository->totalResultsByFilter($filter));
    }

    public function testFindByFilterWithLimitZeroReturnsAllTheFolders(): void
    {
        $repository = $this->createSUT();

        for ($i = 0; $i < 11; ++$i) {
            $repository->add(FolderMother::fromName('Carpeta ' . $i));
        }

        $this->assertCount(10, $repository->findByFilter(new FolderFilter()));
        $this->assertCount(11, $repository->findByFilter(new FolderFilter(limit: 0)));
    }

    public function testFindOneByAccountAndName(): void
    {
        $repository = $this->createSUT();

        $accountId = AccountId::generate();

        $own = FolderMother::fromNameAndAccount('Contratos', $accountId);
        $withoutAccount = FolderMother::fromName('Contratos');

        $repository->add($own);
        $repository->add($withoutAccount);
        $repository->add(FolderMother::fromNameAndAccount('Facturas', AccountId::generate()));

        $found = $repository->findOneByAccountAndName($accountId, 'Contratos');

        $this->assertNotNull($found);
        $this->assertTrue($found->id()->equals($own->id()));

        $foundWithoutAccount = $repository->findOneByAccountAndName(null, 'Contratos');

        $this->assertNotNull($foundWithoutAccount);
        $this->assertTrue($foundWithoutAccount->id()->equals($withoutAccount->id()));

        $this->assertNull($repository->findOneByAccountAndName($accountId, 'Facturas'));
        $this->assertNull($repository->findOneByAccountAndName(AccountId::generate(), 'Contratos'));
        $this->assertNull($repository->findOneByAccountAndName($accountId, 'Contrato'));
    }

    public function testFindOneByAccountAndNameIgnoresCase(): void
    {
        $repository = $this->createSUT();

        $accountId = AccountId::generate();

        $folder = FolderMother::fromNameAndAccount('Contratos', $accountId);

        $repository->add($folder);

        $found = $repository->findOneByAccountAndName($accountId, 'contratos');

        $this->assertNotNull($found);
        $this->assertTrue($found->id()->equals($folder->id()));
    }

    /**
     * @param list<Folder> $folders
     *
     * @return list<string>
     */
    private function namesOf(array $folders): array
    {
        return array_map(static fn(Folder $folder): string => $folder->name(), $folders);
    }

    abstract protected function createSUT(): FolderRepository;
}
