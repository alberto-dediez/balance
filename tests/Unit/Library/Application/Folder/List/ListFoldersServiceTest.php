<?php

declare(strict_types=1);

namespace App\Tests\Unit\Library\Application\Folder\List;

use App\Library\Application\Folder\List\ListFoldersRequest;
use App\Library\Application\Folder\List\ListFoldersService;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\Library\Application\Folder\FolderAuthorizerMother;
use App\Tests\Unit\Library\Domain\Model\Folder\FolderMother;
use App\Tests\Unit\Library\Domain\Model\Folder\FolderRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ListFoldersServiceTest extends TestCase
{
    public function testListsTheFoldersOfTheAccount(): void
    {
        $repository = FolderRepositoryMother::inMemory();

        $accountId = AccountId::generate();

        $repository->add(FolderMother::fromNameAndAccount('Contratos', $accountId));
        $repository->add(FolderMother::fromNameAndAccount('Facturas', $accountId));
        $repository->add(FolderMother::fromNameAndAccount('Ajena', AccountId::generate()));

        $sut = new ListFoldersService($repository, FolderAuthorizerMother::create());

        $result = $sut(
            new ListFoldersRequest(accountId: $accountId),
            UserMother::fromUsernameAndRoleAndAccount('user@dediez.software', Role::User, $accountId),
        );

        $this->assertCount(2, $result->folders);
        $this->assertSame(2, $result->totalResults);
    }

    public function testTheFoldersOfAnotherAccountCannotBeListed(): void
    {
        $sut = new ListFoldersService(FolderRepositoryMother::inMemory(), FolderAuthorizerMother::create());

        $this->expectException(UnauthorizedActionException::class);

        $sut(
            new ListFoldersRequest(accountId: AccountId::generate()),
            UserMother::fromUsernameAndRoleAndAccount('owner@dediez.software', Role::Owner, AccountId::generate()),
        );
    }
}
