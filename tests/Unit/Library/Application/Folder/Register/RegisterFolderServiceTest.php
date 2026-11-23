<?php

declare(strict_types=1);

namespace App\Tests\Unit\Library\Application\Folder\Register;

use App\Library\Application\Folder\Register\RegisterFolderRequest;
use App\Library\Application\Folder\Register\RegisterFolderService;
use App\Library\Domain\Model\Folder\FolderAlreadyExistsException;
use App\Library\Domain\Model\Folder\FolderId;
use App\Library\Domain\Model\Folder\FolderNestingNotAllowedException;
use App\Library\Domain\Model\Folder\FolderNotFoundException;
use App\Library\Domain\Model\Folder\FolderRegistered;
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

class RegisterFolderServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    private InMemoryFolderRepository $repository;

    private RegisterFolderService $sut;

    private AccountId $accountId;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
        $this->repository = FolderRepositoryMother::inMemory();
        $this->sut = new RegisterFolderService($this->repository, FolderAuthorizerMother::create());
        $this->accountId = AccountId::generate();
    }

    public function testUseCase(): void
    {
        $result = ($this->sut)(new RegisterFolderRequest(name: 'Contratos', accountId: $this->accountId), $this->owner());

        $this->assertEquals('Contratos', $result->name);

        $folder = $this->repository->find($result->folderId);

        $this->assertNotNull($folder);
        $this->assertNotNull($folder->accountId());
        $this->assertTrue($folder->accountId()->equals($this->accountId));

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertInstanceOf(FolderRegistered::class, $lastEvent);
        $this->assertEquals('Contratos', $lastEvent->name());
    }

    public function testTheNameIsTrimmed(): void
    {
        $result = ($this->sut)(new RegisterFolderRequest(name: '  Contratos  ', accountId: $this->accountId), $this->owner());

        $this->assertEquals('Contratos', $result->name);
    }

    public function testAUserCannotRegister(): void
    {
        $this->expectException(UnauthorizedActionException::class);

        ($this->sut)(
            new RegisterFolderRequest(name: 'Contratos', accountId: $this->accountId),
            UserMother::fromUsernameAndRoleAndAccount('user@dediez.software', Role::User, $this->accountId),
        );
    }

    public function testTheNameCannotBeRepeatedInTheAccount(): void
    {
        $this->repository->add(FolderMother::fromNameAndAccount('Contratos', $this->accountId));

        $this->expectException(FolderAlreadyExistsException::class);

        ($this->sut)(new RegisterFolderRequest(name: 'Contratos ', accountId: $this->accountId), $this->owner());
    }

    public function testTheSameNameIsAllowedInAnotherAccount(): void
    {
        $this->repository->add(FolderMother::fromNameAndAccount('Contratos', AccountId::generate()));

        $result = ($this->sut)(new RegisterFolderRequest(name: 'Contratos', accountId: $this->accountId), $this->owner());

        $this->assertNotNull($this->repository->find($result->folderId));
    }

    public function testASubfolderIsRegistered(): void
    {
        $contracts = FolderMother::fromNameAndAccount('Contratos', $this->accountId);

        $this->repository->add($contracts);

        $result = ($this->sut)(
            new RegisterFolderRequest(name: '2024', accountId: $this->accountId, parentId: $contracts->id()),
            $this->owner(),
        );

        $this->assertNotNull($result->parentId);
        $this->assertTrue($result->parentId->equals($contracts->id()));

        $folder = $this->repository->find($result->folderId);

        $this->assertNotNull($folder);
        $this->assertTrue($folder->isSubfolder());
        $this->assertTrue($folder->parentId()?->equals($contracts->id()) ?? false);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertInstanceOf(FolderRegistered::class, $lastEvent);
        $this->assertTrue($lastEvent->parentId()?->equals($contracts->id()) ?? false);
    }

    public function testAnUnknownParentIsRejected(): void
    {
        $this->expectException(FolderNotFoundException::class);

        ($this->sut)(
            new RegisterFolderRequest(name: '2024', accountId: $this->accountId, parentId: FolderId::generate()),
            $this->owner(),
        );
    }

    public function testAParentOfAnotherAccountIsRejected(): void
    {
        $foreign = FolderMother::fromNameAndAccount('Contratos', AccountId::generate());

        $this->repository->add($foreign);

        $this->expectException(FolderNotFoundException::class);

        ($this->sut)(
            new RegisterFolderRequest(name: '2024', accountId: $this->accountId, parentId: $foreign->id()),
            $this->owner(),
        );
    }

    public function testASubfolderCannotContainFolders(): void
    {
        $contracts = FolderMother::fromNameAndAccount('Contratos', $this->accountId);
        $subfolder = FolderMother::subfolderOf($contracts, '2024');

        $this->repository->add($contracts);
        $this->repository->add($subfolder);

        $this->expectException(FolderNestingNotAllowedException::class);

        ($this->sut)(
            new RegisterFolderRequest(name: 'Enero', accountId: $this->accountId, parentId: $subfolder->id()),
            $this->owner(),
        );
    }

    public function testTheNameCannotBeRepeatedInTheSameFolder(): void
    {
        $contracts = FolderMother::fromNameAndAccount('Contratos', $this->accountId);

        $this->repository->add($contracts);
        $this->repository->add(FolderMother::subfolderOf($contracts, '2024'));

        $this->expectException(FolderAlreadyExistsException::class);

        ($this->sut)(
            new RegisterFolderRequest(name: '2024', accountId: $this->accountId, parentId: $contracts->id()),
            $this->owner(),
        );
    }

    public function testTheSameNameIsAllowedInAnotherFolder(): void
    {
        $contracts = FolderMother::fromNameAndAccount('Contratos', $this->accountId);
        $invoices = FolderMother::fromNameAndAccount('Facturas', $this->accountId);

        $this->repository->add($contracts);
        $this->repository->add($invoices);
        $this->repository->add(FolderMother::subfolderOf($contracts, '2024'));

        $result = ($this->sut)(
            new RegisterFolderRequest(name: '2024', accountId: $this->accountId, parentId: $invoices->id()),
            $this->owner(),
        );

        $this->assertNotNull($this->repository->find($result->folderId));
    }

    public function testASubfolderCanBeNamedAsAFolder(): void
    {
        $contracts = FolderMother::fromNameAndAccount('Contratos', $this->accountId);

        $this->repository->add($contracts);
        $this->repository->add(FolderMother::fromNameAndAccount('Facturas', $this->accountId));

        $result = ($this->sut)(
            new RegisterFolderRequest(name: 'Facturas', accountId: $this->accountId, parentId: $contracts->id()),
            $this->owner(),
        );

        $this->assertNotNull($this->repository->find($result->folderId));
    }

    public function testAUserCannotRegisterASubfolder(): void
    {
        $contracts = FolderMother::fromNameAndAccount('Contratos', $this->accountId);

        $this->repository->add($contracts);

        $this->expectException(UnauthorizedActionException::class);

        ($this->sut)(
            new RegisterFolderRequest(name: '2024', accountId: $this->accountId, parentId: $contracts->id()),
            UserMother::fromUsernameAndRoleAndAccount('user@dediez.software', Role::User, $this->accountId),
        );
    }

    public function testIsHandled(): void
    {
        $this->assertTrue($this->sut->isHandled(new RegisterFolderRequest(name: 'Contratos')));
    }

    private function owner(): User
    {
        return UserMother::fromUsernameAndRoleAndAccount('owner@dediez.software', Role::Owner, $this->accountId);
    }
}
