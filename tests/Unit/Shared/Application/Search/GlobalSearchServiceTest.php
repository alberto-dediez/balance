<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Search;

use App\Security\Application\UnauthorizedActionException;
use App\Shared\Application\Search\GlobalSearchRequest;
use App\Shared\Application\Search\GlobalSearchService;
use App\Shared\Application\Search\SearchCategoryResults;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class GlobalSearchServiceTest extends TestCase
{
    public function testReturnsTheCategoriesOrderedByPosition(): void
    {
        $sut = new GlobalSearchService([
            FakeSearchProvider::withResults('users', 150),
            FakeSearchProvider::withResults('accounts', 140),
            FakeSearchProvider::withResults('customers', 10),
        ]);

        $response = $sut(new GlobalSearchRequest('acme'), UserMother::adminUser());

        $this->assertTrue($response->searched);
        $this->assertSame(['customers', 'accounts', 'users'], $this->keys($response->categories));
    }

    public function testOmitsTheCategoriesWithoutResults(): void
    {
        $sut = new GlobalSearchService([
            FakeSearchProvider::withResults('accounts', 140, 0),
            FakeSearchProvider::withResults('users', 150, 2),
        ]);

        $response = $sut(new GlobalSearchRequest('acme'), UserMother::adminUser());

        $this->assertSame(['users'], $this->keys($response->categories));
        $this->assertCount(2, $response->categories[0]->results);
    }

    public function testOmitsTheProvidersNotAvailableToTheUser(): void
    {
        $accounts = FakeSearchProvider::withResults('accounts', 140, available: false);
        $sut = new GlobalSearchService([
            $accounts,
            FakeSearchProvider::withResults('users', 150),
        ]);

        $response = $sut(new GlobalSearchRequest('acme'), UserMother::ownerUser());

        $this->assertSame(['users'], $this->keys($response->categories));
        $this->assertSame(0, $accounts->calls);
    }

    public function testAShortTermDoesNotCallAnyProvider(): void
    {
        $users = FakeSearchProvider::withResults('users', 150);
        $sut = new GlobalSearchService([$users]);

        $response = $sut(new GlobalSearchRequest(' a '), UserMother::adminUser());

        $this->assertFalse($response->searched);
        $this->assertSame([], $response->categories);
        $this->assertSame('a', $response->term->value);
        $this->assertSame(0, $users->calls);
    }

    public function testPassesTheLimitPerCategoryToEveryProvider(): void
    {
        $accounts = FakeSearchProvider::withResults('accounts', 140);
        $users = FakeSearchProvider::withResults('users', 150);
        $sut = new GlobalSearchService([$accounts, $users]);

        $sut(new GlobalSearchRequest('acme', 3), UserMother::adminUser());

        $this->assertSame(3, $accounts->lastLimit);
        $this->assertSame(3, $users->lastLimit);
    }

    public function testFailsWhenNoProviderIsAvailable(): void
    {
        $sut = new GlobalSearchService([
            FakeSearchProvider::withResults('accounts', 140, available: false),
        ]);

        $this->expectException(UnauthorizedActionException::class);

        $sut(new GlobalSearchRequest('acme'), UserMother::registeredUser());
    }

    public function testIsAvailableWhenAtLeastOneProviderIs(): void
    {
        $sut = new GlobalSearchService([
            FakeSearchProvider::withResults('accounts', 140, available: false),
            FakeSearchProvider::withResults('users', 150),
        ]);

        $this->assertTrue($sut->isAvailableTo(UserMother::ownerUser()));
    }

    public function testIsNotAvailableWithoutProviders(): void
    {
        $sut = new GlobalSearchService([]);

        $this->assertFalse($sut->isAvailableTo(UserMother::adminUser()));
    }

    /**
     * @param list<SearchCategoryResults> $categories
     *
     * @return list<string>
     */
    private function keys(array $categories): array
    {
        return array_map(fn(SearchCategoryResults $categoryResults): string => $categoryResults->category->key, $categories);
    }
}
