<?php

declare(strict_types=1);

namespace App\Tests\Unit\Library\Application\Folder\Register;

use App\Library\Application\Folder\Register\RegisterFolderRequest;
use App\Library\Application\Folder\Register\RegisterFolderService;
use App\Library\Domain\Model\Folder\FolderAlreadyExistsException;
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

    public function testIsHandled(): void
    {
        $this->assertTrue($this->sut->isHandled(new RegisterFolderRequest(name: 'Contratos')));
    }

    private function owner(): User
    {
        return UserMother::fromUsernameAndRoleAndAccount('owner@dediez.software', Role::Owner, $this->accountId);
    }
}
