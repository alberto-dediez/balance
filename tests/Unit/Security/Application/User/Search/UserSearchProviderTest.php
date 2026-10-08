<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Application\User\Search;

use App\Security\Application\User\Search\UserSearchProvider;
use App\Security\Application\User\UserAuthorizer;
use App\Security\Domain\Model\User\Role;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryAccountRepository;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryUserRepository;
use App\Shared\Application\Search\SearchResult;
use App\Shared\Application\Search\SearchTerm;
use App\Tests\Unit\Security\Application\User\UserAuthorizerMother;
use App\Tests\Unit\Security\Domain\Model\Account\AccountMother;
use App\Tests\Unit\Security\Domain\Model\Account\AccountRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Security\Domain\Model\User\UserRepositoryMother;
use PHPUnit\Framework\TestCase;

class UserSearchProviderTest extends TestCase
{
    private InMemoryUserRepository $repository;

    private InMemoryAccountRepository $accountRepository;

    protected function setUp(): void
    {
        $this->repository = UserRepositoryMother::inMemory();
        $this->accountRepository = AccountRepositoryMother::inMemory();
    }

    public function testAnAdminFindsUsersOfAnyAccountBySubstring(): void
    {
        $acme = AccountMother::fromName('Acme');
        $other = AccountMother::fromName('Other');
        $this->accountRepository->add($acme);
        $this->accountRepository->add($other);
        $this->repository->add(UserMother::fromUsernameAndRoleAndAccount('owner@acme.com', Role::Owner, $acme->id(), $acme));
        $this->repository->add(UserMother::fromUsernameAndRoleAndAccount('user@acme.com', Role::User, $other->id(), $other));
        $this->repository->add(UserMother::fromUsername('nobody@example.com'));

        $sut = $this->createSUT(UserAuthorizerMother::create());

        $results = $sut->search(new SearchTerm('ACME'), UserMother::adminUser(), 5);

        $this->assertSame(['owner@acme.com', 'user@acme.com'], $this->titles($results));
        $this->assertSame('admin_users_edit', $results[0]->route);
        $this->assertSame('Acme', $results[0]->subtitle);
        $this->assertSame('Other', $results[1]->subtitle);
    }

    public function testAnOwnerOnlyFindsTheUsersOfItsAccount(): void
    {
        $acme = AccountMother::fromName('Acme');
        $other = AccountMother::fromName('Other');
        $this->accountRepository->add($acme);
        $this->accountRepository->add($other);
        $owner = UserMother::fromUsernameAndRoleAndAccount('owner@acme.com', Role::Owner, $acme->id(), $acme);
        $mate = UserMother::fromUsernameAndRoleAndAccount('mate@acme.com', Role::User, $acme->id(), $acme);
        $this->repository->add($owner);
        $this->repository->add($mate);
        $this->repository->add(UserMother::fromUsernameAndRoleAndAccount('stranger@acme.com', Role::User, $other->id(), $other));

        $sut = $this->createSUT(UserAuthorizerMother::withCurrentAccount($acme->id()));

        $this->assertTrue($sut->isAvailableTo($owner));

        $results = $sut->search(new SearchTerm('acme'), $owner, 5);

        $this->assertSame(['owner@acme.com', 'mate@acme.com'], $this->titles($results));
        $this->assertSame('users_edit', $results[1]->route);
        $this->assertSame(['userId' => (string) $mate->id()], $results[1]->routeParameters);
    }

    public function testAUserIsNotAvailable(): void
    {
        $acme = AccountMother::fromName('Acme');
        $this->accountRepository->add($acme);
        $user = UserMother::fromUsernameAndRoleAndAccount('user@acme.com', Role::User, $acme->id(), $acme);
        $this->repository->add($user);

        $sut = $this->createSUT(UserAuthorizerMother::withCurrentAccount($acme->id()));

        $this->assertFalse($sut->isAvailableTo($user));
        $this->assertSame([], $sut->search(new SearchTerm('acme'), $user, 5));
    }

    public function testRespectsTheLimit(): void
    {
        foreach (range(1, 4) as $index) {
            $this->repository->add(UserMother::fromUsername(sprintf('acme-%d@example.com', $index)));
        }

        $sut = $this->createSUT(UserAuthorizerMother::create());

        $this->assertCount(3, $sut->search(new SearchTerm('acme'), UserMother::adminUser(), 3));
    }

    public function testDoesNotFindRemovedUsers(): void
    {
        $removed = UserMother::fromUsername('removed@acme.com');
        $removed->remove();
        $this->repository->add($removed);
        $this->repository->add(UserMother::fromUsername('active@acme.com'));

        $sut = $this->createSUT(UserAuthorizerMother::create());

        $this->assertSame(['active@acme.com'], $this->titles($sut->search(new SearchTerm('acme'), UserMother::adminUser(), 5)));
    }

    private function createSUT(UserAuthorizer $authorizer): UserSearchProvider
    {
        return new UserSearchProvider($this->repository, $this->accountRepository, $authorizer);
    }

    /**
     * @param list<SearchResult> $results
     *
     * @return list<string>
     */
    private function titles(array $results): array
    {
        return array_map(fn(SearchResult $result): string => $result->title, $results);
    }
}
