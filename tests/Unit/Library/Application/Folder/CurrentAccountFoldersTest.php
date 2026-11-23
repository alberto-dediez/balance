<?php

declare(strict_types=1);

namespace App\Tests\Unit\Library\Application\Folder;

use App\Library\Application\Folder\CurrentAccountFolders;
use App\Library\Domain\Model\Folder\Folder;
use App\Library\Infrastructure\Persistence\InMemory\Repository\InMemoryFolderRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Tests\Unit\Library\Domain\Model\Folder\FolderMother;
use App\Tests\Unit\Library\Domain\Model\Folder\FolderRepositoryMother;
use App\Tests\Unit\Shared\Application\CurrentAccount\StubCurrentAccountIdProvider;
use PHPUnit\Framework\TestCase;

class CurrentAccountFoldersTest extends TestCase
{
    private InMemoryFolderRepository $repository;

    private AccountId $accountId;

    protected function setUp(): void
    {
        $this->repository = FolderRepositoryMother::inMemory();
        $this->accountId = AccountId::generate();

        $this->repository->add(FolderMother::fromNameAndAccount('Facturas', $this->accountId));
        $this->repository->add(FolderMother::fromNameAndAccount('Contratos', $this->accountId));
        $this->repository->add(FolderMother::fromNameAndAccount('Contratos ajenos', AccountId::generate()));
        $this->repository->add(FolderMother::fromName('Sin cuenta'));
    }

    public function testAllReturnsTheFoldersOfTheCurrentAccountSortedByName(): void
    {
        $sut = new CurrentAccountFolders($this->repository, new StubCurrentAccountIdProvider($this->accountId));

        $this->assertSame(['Contratos', 'Facturas'], $this->namesOf($sut->all()));
    }

    public function testWithoutCurrentAccountItReturnsTheFoldersWithoutAccount(): void
    {
        $sut = new CurrentAccountFolders($this->repository, new StubCurrentAccountIdProvider());

        $this->assertSame(['Sin cuenta'], $this->namesOf($sut->all()));
    }

    public function testFindIgnoresTheFoldersOfAnotherAccount(): void
    {
        $own = FolderMother::fromNameAndAccount('Propia', $this->accountId);
        $foreign = FolderMother::fromNameAndAccount('Ajena', AccountId::generate());

        $this->repository->add($own);
        $this->repository->add($foreign);

        $sut = new CurrentAccountFolders($this->repository, new StubCurrentAccountIdProvider($this->accountId));

        $this->assertInstanceOf(Folder::class, $sut->find($own->id()));
        $this->assertNull($sut->find($foreign->id()));
    }

    public function testMatchingNameIsPartialAndEmptyForAnEmptyText(): void
    {
        $sut = new CurrentAccountFolders($this->repository, new StubCurrentAccountIdProvider($this->accountId));

        $this->assertSame(['Contratos'], $this->namesOf($sut->matchingName('contr')));
        $this->assertSame([], $sut->matchingName(''));
    }

    public function testRootsSubfoldersAndPathsFollowTheTree(): void
    {
        $contracts = $this->repository->findOneByAccountAndName($this->accountId, 'Contratos');
        $invoices = $this->repository->findOneByAccountAndName($this->accountId, 'Facturas');

        $this->assertNotNull($contracts);
        $this->assertNotNull($invoices);

        $second = FolderMother::subfolderOf($contracts, '2025');
        $first = FolderMother::subfolderOf($contracts, '2024');
        $invoicesSubfolder = FolderMother::subfolderOf($invoices, 'Abril');
        $foreignParent = FolderMother::fromNameAndAccount('Ajena', AccountId::generate());

        $this->repository->add($second);
        $this->repository->add($first);
        $this->repository->add($invoicesSubfolder);
        $this->repository->add($foreignParent);
        $this->repository->add(FolderMother::subfolderOf($foreignParent, 'Ajena 2024'));

        $sut = new CurrentAccountFolders($this->repository, new StubCurrentAccountIdProvider($this->accountId));

        $this->assertSame(['Contratos', 'Facturas'], $this->namesOf($sut->roots()));
        $this->assertSame(['2024', '2025'], $this->namesOf($sut->subfoldersOf($contracts->id())));
        $this->assertSame([], $sut->subfoldersOf($foreignParent->id()));

        $this->assertSame(
            [
                (string) $contracts->id() => 'Contratos',
                (string) $first->id() => 'Contratos / 2024',
                (string) $second->id() => 'Contratos / 2025',
                (string) $invoices->id() => 'Facturas',
                (string) $invoicesSubfolder->id() => 'Facturas / Abril',
            ],
            $sut->paths(),
        );

        $this->assertSame(
            [(string) $contracts->id(), (string) $first->id(), (string) $second->id()],
            array_map('strval', $sut->idsWithSubfolders($contracts->id())),
        );
    }

    public function testTreeGroupsTheSubfoldersUnderTheirFolderOfTheCurrentAccount(): void
    {
        $contracts = $this->repository->findOneByAccountAndName($this->accountId, 'Contratos');

        $this->assertNotNull($contracts);

        $foreignParent = FolderMother::fromNameAndAccount('Ajena', AccountId::generate());

        $this->repository->add(FolderMother::subfolderOf($contracts, '2025'));
        $this->repository->add(FolderMother::subfolderOf($contracts, '2024'));
        $this->repository->add($foreignParent);
        $this->repository->add(FolderMother::subfolderOf($foreignParent, 'Ajena 2024'));

        $sut = new CurrentAccountFolders($this->repository, new StubCurrentAccountIdProvider($this->accountId));

        $tree = $sut->tree();

        $this->assertSame(['Contratos', 'Facturas'], $this->namesOf(array_map(static fn(array $branch): Folder => $branch['folder'], $tree)));
        $this->assertSame(['2024', '2025'], $this->namesOf($tree[0]['subfolders']));
        $this->assertSame([], $tree[1]['subfolders']);
    }

    public function testMatchingNameIncludesTheSubfoldersOfAMatchingFolderWithoutDuplicates(): void
    {
        $contracts = $this->repository->findOneByAccountAndName($this->accountId, 'Contratos');
        $invoices = $this->repository->findOneByAccountAndName($this->accountId, 'Facturas');

        $this->assertNotNull($contracts);
        $this->assertNotNull($invoices);

        $this->repository->add(FolderMother::subfolderOf($contracts, 'Contratos 2024'));
        $this->repository->add(FolderMother::subfolderOf($contracts, '2025'));
        $this->repository->add(FolderMother::subfolderOf($invoices, 'Contratos de alquiler'));

        $sut = new CurrentAccountFolders($this->repository, new StubCurrentAccountIdProvider($this->accountId));

        $names = $this->namesOf($sut->matchingName('contratos'));

        sort($names);

        $this->assertSame(['2025', 'Contratos', 'Contratos 2024', 'Contratos de alquiler'], $names);
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
}
