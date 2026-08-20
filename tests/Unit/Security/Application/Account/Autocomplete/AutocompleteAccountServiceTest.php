<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Application\Account\Autocomplete;

use App\Security\Application\Account\AccountAuthorizer;
use App\Security\Application\Account\Autocomplete\AutocompleteAccountRequest;
use App\Security\Application\Account\Autocomplete\AutocompleteAccountService;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\User\Role;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryAccountRepository;
use App\Tests\Unit\Security\Domain\Model\Account\AccountMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class AutocompleteAccountServiceTest extends TestCase
{
    public function testReturnsActiveAccountsMatchingQuery(): void
    {
        $repository = $this->buildRepository();

        $matching = AccountMother::fromName('Acme Corporation');
        $other = AccountMother::fromName('Other Company');

        $repository->add($matching);
        $repository->add($other);

        $sut = $this->createSUT($repository);

        $result = $sut(
            new AutocompleteAccountRequest(query: 'Acme'),
            UserMother::adminUser(),
        );

        $this->assertCount(1, $result->elements);
        $this->assertSame((string) $matching->id(), $result->elements[0]->value);
    }

    public function testOnlyActiveAccountsAreReturned(): void
    {
        $repository = $this->buildRepository();

        $active = AccountMother::fromName('Active Account');
        $inactive = AccountMother::fromName('Inactive Account');
        $inactive->changeStatus(false);

        $repository->add($active);
        $repository->add($inactive);

        $sut = $this->createSUT($repository);

        $result = $sut(
            new AutocompleteAccountRequest(query: ''),
            UserMother::adminUser(),
        );

        $this->assertCount(1, $result->elements);
        $this->assertSame((string) $active->id(), $result->elements[0]->value);
    }

    public function testOwnerIsNotAuthorized(): void
    {
        $repository = $this->buildRepository();
        $repository->add(AccountMother::registeredAccount());

        $sut = $this->createSUT($repository);

        $this->expectException(UnauthorizedActionException::class);

        $sut(
            new AutocompleteAccountRequest(query: ''),
            UserMother::fromUsernameAndRole('owner', Role::Owner),
        );
    }

    public function testUserIsNotAuthorized(): void
    {
        $repository = $this->buildRepository();
        $repository->add(AccountMother::registeredAccount());

        $sut = $this->createSUT($repository);

        $this->expectException(UnauthorizedActionException::class);

        $sut(
            new AutocompleteAccountRequest(query: ''),
            UserMother::fromUsernameAndRole('user', Role::User),
        );
    }

    private function buildRepository(): InMemoryAccountRepository
    {
        return new InMemoryAccountRepository();
    }

    private function createSUT(InMemoryAccountRepository $repository): AutocompleteAccountService
    {
        return new AutocompleteAccountService($repository, new AccountAuthorizer());
    }
}
