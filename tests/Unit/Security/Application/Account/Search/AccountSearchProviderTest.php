<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Application\Account\Search;

use App\Security\Application\Account\Search\AccountSearchProvider;
use App\Security\Domain\Model\User\Role;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryAccountRepository;
use App\Shared\Application\Search\SearchResult;
use App\Shared\Application\Search\SearchTerm;
use App\Tests\Unit\Security\Application\Account\AccountAuthorizerMother;
use App\Tests\Unit\Security\Domain\Model\Account\AccountMother;
use App\Tests\Unit\Security\Domain\Model\Account\AccountRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class AccountSearchProviderTest extends TestCase
{
    private InMemoryAccountRepository $repository;

    private AccountSearchProvider $sut;

    protected function setUp(): void
    {
        $this->repository = AccountRepositoryMother::inMemory();
        $this->sut = new AccountSearchProvider($this->repository, AccountAuthorizerMother::create());
    }

    public function testAnAdminFindsAccountsBySubstring(): void
    {
        $acme = AccountMother::fromName('Acme Corp');
        $this->repository->add($acme);
        $this->repository->add(AccountMother::fromName('Globex'));

        $results = $this->sut->search(new SearchTerm('acme'), UserMother::adminUser(), 5);

        $this->assertSame(['Acme Corp'], $this->titles($results));
        $this->assertSame('admin_accounts_edit', $results[0]->route);
        $this->assertSame(['accountId' => (string) $acme->id()], $results[0]->routeParameters);
        $this->assertNull($results[0]->subtitle);
    }

    public function testIncludesInactiveAccounts(): void
    {
        $inactive = AccountMother::fromName('Acme Inactive');
        $inactive->changeStatus(false);
        $this->repository->add($inactive);

        $this->assertSame(['Acme Inactive'], $this->titles($this->sut->search(new SearchTerm('acme'), UserMother::adminUser(), 5)));
    }

    public function testExcludesRemovedAccounts(): void
    {
        $removed = AccountMother::fromName('Acme Removed');
        $removed->remove();
        $this->repository->add($removed);
        $this->repository->add(AccountMother::fromName('Acme'));

        $this->assertSame(['Acme'], $this->titles($this->sut->search(new SearchTerm('acme'), UserMother::adminUser(), 5)));
    }

    public function testRespectsTheLimit(): void
    {
        foreach (range(1, 6) as $index) {
            $this->repository->add(AccountMother::fromName(sprintf('Acme %d', $index)));
        }

        $this->assertCount(5, $this->sut->search(new SearchTerm('acme'), UserMother::adminUser(), 5));
    }

    public function testAnOwnerIsNotAvailable(): void
    {
        $acme = AccountMother::fromName('Acme');
        $this->repository->add($acme);
        $owner = UserMother::fromUsernameAndRoleAndAccount('owner@acme.com', Role::Owner, $acme->id(), $acme);

        $this->assertFalse($this->sut->isAvailableTo($owner));
        $this->assertSame([], $this->sut->search(new SearchTerm('acme'), $owner, 5));
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
