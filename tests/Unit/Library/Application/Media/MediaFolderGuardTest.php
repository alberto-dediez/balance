<?php

declare(strict_types=1);

namespace App\Tests\Unit\Library\Application\Media;

use App\Library\Application\Media\MediaFolderGuard;
use App\Library\Domain\Model\Folder\FolderId;
use App\Library\Domain\Model\Folder\FolderNotFoundException;
use App\Library\Infrastructure\Persistence\InMemory\Repository\InMemoryFolderRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Tests\Unit\Library\Application\Folder\FolderAuthorizerMother;
use App\Tests\Unit\Library\Domain\Model\Folder\FolderMother;
use App\Tests\Unit\Library\Domain\Model\Folder\FolderRepositoryMother;
use App\Tests\Unit\Library\Domain\Model\Media\MediaMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class MediaFolderGuardTest extends TestCase
{
    private InMemoryFolderRepository $repository;

    private MediaFolderGuard $sut;

    private AccountId $accountId;

    protected function setUp(): void
    {
        $this->repository = FolderRepositoryMother::inMemory();
        $this->sut = new MediaFolderGuard($this->repository, FolderAuthorizerMother::create());
        $this->accountId = AccountId::generate();
    }

    public function testNoFolderIsAlwaysAssignable(): void
    {
        $this->sut->assertAssignable(MediaMother::fromNameAndAccount('logo.png', $this->accountId), null, $this->user());

        $this->addToAssertionCount(1);
    }

    public function testAnUnknownFolderIsRejected(): void
    {
        $this->expectException(FolderNotFoundException::class);

        $this->sut->assertAssignable(MediaMother::fromNameAndAccount('logo.png', $this->accountId), FolderId::generate(), $this->user());
    }

    public function testAFolderOfAnotherAccountIsRejected(): void
    {
        $folder = FolderMother::fromNameAndAccount('Contratos', AccountId::generate());

        $this->repository->add($folder);

        $this->expectException(FolderNotFoundException::class);

        $this->sut->assertAssignable(MediaMother::fromNameAndAccount('logo.png', $this->accountId), $folder->id(), $this->user());
    }

    public function testAFolderWithoutAccountIsRejectedForAMediaWithAccount(): void
    {
        $folder = FolderMother::fromName('Contratos');

        $this->repository->add($folder);

        $this->expectException(FolderNotFoundException::class);

        $this->sut->assertAssignable(MediaMother::fromNameAndAccount('logo.png', $this->accountId), $folder->id(), $this->user());
    }

    public function testAFolderOfTheSameAccountIsAssignable(): void
    {
        $folder = FolderMother::fromNameAndAccount('Contratos', $this->accountId);

        $this->repository->add($folder);

        $this->sut->assertAssignable(MediaMother::fromNameAndAccount('logo.png', $this->accountId), $folder->id(), $this->user());

        $this->addToAssertionCount(1);
    }

    public function testASubfolderOfTheSameAccountIsAssignable(): void
    {
        $folder = FolderMother::fromNameAndAccount('Contratos', $this->accountId);
        $subfolder = FolderMother::subfolderOf($folder, '2024');

        $this->repository->add($folder);
        $this->repository->add($subfolder);

        $this->sut->assertAssignable(MediaMother::fromNameAndAccount('logo.png', $this->accountId), $subfolder->id(), $this->user());

        $this->addToAssertionCount(1);
    }

    private function user(): User
    {
        return UserMother::fromUsernameAndRoleAndAccount('user@dediez.software', Role::User, $this->accountId);
    }
}
