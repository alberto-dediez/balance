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
