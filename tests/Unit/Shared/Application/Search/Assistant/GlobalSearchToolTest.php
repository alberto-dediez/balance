<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Search\Assistant;

use App\Shared\Application\Assistant\ToolArguments;
use App\Shared\Application\Assistant\ToolArgumentsFactory;
use App\Shared\Application\Assistant\ToolContext;
use App\Shared\Application\Assistant\ToolMode;
use App\Shared\Application\Search\Assistant\GlobalSearchTool;
use App\Shared\Application\Search\GlobalSearchService;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Application\Search\FakeSearchProvider;
use PHPUnit\Framework\TestCase;

class GlobalSearchToolTest extends TestCase
{
    private FakeSearchProvider $customers;

    private GlobalSearchTool $sut;

    protected function setUp(): void
    {
        $this->customers = FakeSearchProvider::withResults('customers', 10, 2);

        $this->sut = new GlobalSearchTool(new GlobalSearchService([
            FakeSearchProvider::withResults('navigation', 1),
            $this->customers,
            FakeSearchProvider::withResults('secrets', 20),
            FakeSearchProvider::withResults('counterparties', 30),
            FakeSearchProvider::withResults('candidates', 35),
            FakeSearchProvider::withResults('tasks', 40, 1),
        ]));
    }

    public function testReturnsTheResultsByCategory(): void
    {
        $result = $this->sut->execute($this->arguments(['term' => 'acme']), UserMother::adminUser(), $this->context());

        $this->assertSame(['customers', 'tasks'], array_column($result->data['categories'], 'key'));
        $this->assertSame('Customers', $result->data['categories'][0]['label']);
        $this->assertSame([
            ['title' => 'customers 1', 'subtitle' => null, 'href' => 'route:customers_edit?id=1'],
            ['title' => 'customers 2', 'subtitle' => null, 'href' => 'route:customers_edit?id=2'],
        ], $result->data['categories'][0]['results']);
    }

    public function testNeverReturnsNavigationSecretsCounterpartiesNorCandidates(): void
    {
        $result = $this->sut->execute($this->arguments(['term' => 'acme']), UserMother::adminUser(), $this->context());

        $keys = array_column($result->data['categories'], 'key');
        $this->assertNotContains('navigation', $keys);
        $this->assertNotContains('secrets', $keys);
        $this->assertNotContains('counterparties', $keys);
        $this->assertNotContains('candidates', $keys);
    }

    public function testAShortTermHasNoResults(): void
    {
        $result = $this->sut->execute($this->arguments(['term' => 'a']), UserMother::adminUser(), $this->context());

        $this->assertSame([], $result->data['categories']);
        $this->assertSame(0, $this->customers->calls);
    }

    public function testTheLimitIsPassedToTheProvidersAndCapped(): void
    {
        $this->sut->execute($this->arguments(['term' => 'acme', 'limit' => 3]), UserMother::adminUser(), $this->context());
        $this->assertSame(3, $this->customers->lastLimit);

        $this->sut->execute($this->arguments(['term' => 'acme']), UserMother::adminUser(), $this->context());
        $this->assertSame(5, $this->customers->lastLimit);
    }

    public function testIsAReadToolAvailableWhenAProviderIs(): void
    {
        $this->assertSame(ToolMode::Read, $this->sut->mode());
        $this->assertTrue($this->sut->isAvailableTo(UserMother::adminUser()));

        $without = new GlobalSearchTool(new GlobalSearchService([FakeSearchProvider::withResults('customers', 10, available: false)]));
        $this->assertFalse($without->isAvailableTo(UserMother::adminUser()));
    }

    /** @param array<string, mixed> $raw */
    private function arguments(array $raw): ToolArguments
    {
        return ToolArgumentsFactory::create($this->sut->parameters(), $raw, $this->context());
    }

    private function context(): ToolContext
    {
        return new ToolContext(new \DateTimeImmutable('2026-11-11 10:00:00'), new \DateTimeZone('Europe/Madrid'));
    }
}
