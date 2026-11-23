<?php

declare(strict_types=1);

namespace App\Tests\Unit\Library\Application\Folder\Update;

use App\Library\Application\Folder\Update\UpdateFolderRequest;
use App\Library\Application\Folder\Update\UpdateFolderService;
use App\Library\Domain\Model\Folder\FolderAlreadyExistsException;
use App\Library\Domain\Model\Folder\FolderId;
use App\Library\Domain\Model\Folder\FolderNotFoundException;
use App\Library\Domain\Model\Folder\FolderRenamed;
use App\Library\Infrastructure\Persistence\InMemory\Repository\InMemoryFolderRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Library\Application\Folder\FolderAuthorizerMother;
use App\Tests\Unit\Library\Domain\Model\Folder\FolderMother;
use App\Tests\Unit\Library\Domain\Model\Folder\FolderRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class UpdateFolderServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    private InMemoryFolderRepository $repository;

    private UpdateFolderService $sut;

    private AccountId $accountId;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
        $this->repository = FolderRepositoryMother::inMemory();
        $this->sut = new UpdateFolderService($this->repository, FolderAuthorizerMother::create());
        $this->accountId = AccountId::generate();
    }

    public function testUseCase(): void
    {
        $folder = FolderMother::fromNameAndAccount('Contratos', $this->accountId);

        $this->repository->add($folder);

        $result = ($this->sut)(new UpdateFolderRequest(folderId: $folder->id(), name: 'Facturas'), $this->owner());

        $this->assertEquals('Facturas', $result->name);
        $this->assertEquals('Facturas', $this->repository->find($folder->id())?->name());
        $this->assertInstanceOf(FolderRenamed::class, $this->spySubscriber->lastEvent());
    }

    public function testTheSameNameRecordsNothing(): void
    {
        $folder = FolderMother::fromNameAndAccount('Contratos', $this->accountId);

        $this->repository->add($folder);

        ($this->sut)(new UpdateFolderRequest(folderId: $folder->id(), name: 'Contratos'), $this->owner());

        $this->assertNotInstanceOf(FolderRenamed::class, $this->spySubscriber->lastEvent());
    }

    public function testTheNameCannotBeRepeatedInTheAccount(): void
    {
        $folder = FolderMother::fromNameAndAccount('Contratos', $this->accountId);

        $this->repository->add($folder);
        $this->repository->add(FolderMother::fromNameAndAccount('Facturas', $this->accountId));

        $this->expectException(FolderAlreadyExistsException::class);

        ($this->sut)(new UpdateFolderRequest(folderId: $folder->id(), name: 'Facturas'), $this->owner());
    }

    public function testASubfolderCannotTakeTheNameOfASibling(): void
    {
        $contracts = FolderMother::fromNameAndAccount('Contratos', $this->accountId);
        $subfolder = FolderMother::subfolderOf($contracts, '2024');

        $this->repository->add($contracts);
        $this->repository->add($subfolder);
        $this->repository->add(FolderMother::subfolderOf($contracts, '2025'));

        $this->expectException(FolderAlreadyExistsException::class);

        ($this->sut)(new UpdateFolderRequest(folderId: $subfolder->id(), name: '2025'), $this->owner());
    }

    public function testASubfolderCanTakeTheNameOfAFolderOrOfASubfolderOfAnotherFolder(): void
    {
        $contracts = FolderMother::fromNameAndAccount('Contratos', $this->accountId);
        $invoices = FolderMother::fromNameAndAccount('Facturas', $this->accountId);
        $subfolder = FolderMother::subfolderOf($contracts, '2024');

        $this->repository->add($contracts);
        $this->repository->add($invoices);
        $this->repository->add($subfolder);
        $this->repository->add(FolderMother::subfolderOf($invoices, '2025'));

        $result = ($this->sut)(new UpdateFolderRequest(folderId: $subfolder->id(), name: '2025'), $this->owner());

        $this->assertEquals('2025', $result->name);

        $result = ($this->sut)(new UpdateFolderRequest(folderId: $subfolder->id(), name: 'Facturas'), $this->owner());

        $this->assertEquals('Facturas', $result->name);
    }

    public function testUnknownFolder(): void
    {
        $this->expectException(FolderNotFoundException::class);

        ($this->sut)(new UpdateFolderRequest(folderId: FolderId::generate(), name: 'Facturas'), $this->owner());
    }

    public function testAUserCannotRename(): void
    {
        $folder = FolderMother::fromNameAndAccount('Contratos', $this->accountId);

        $this->repository->add($folder);

        $this->expectException(UnauthorizedActionException::class);

        ($this->sut)(
            new UpdateFolderRequest(folderId: $folder->id(), name: 'Facturas'),
            UserMother::fromUsernameAndRoleAndAccount('user@dediez.software', Role::User, $this->accountId),
        );
    }

    public function testIsHandled(): void
    {
        $this->assertTrue($this->sut->isHandled(new UpdateFolderRequest(folderId: FolderId::generate())));
    }

    private function owner(): User
    {
        return UserMother::fromUsernameAndRoleAndAccount('owner@dediez.software', Role::Owner, $this->accountId);
    }
}
