<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Application\Account\Assistant;

use App\Security\Application\Account\Assistant\ListAccountsTool;
use App\Security\Application\Account\List\ListAccountsService;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryAccountRepository;
use App\Shared\Application\Assistant\ToolArguments;
use App\Shared\Application\Assistant\ToolArgumentsFactory;
use App\Shared\Application\Assistant\ToolContext;
use App\Shared\Application\Assistant\ToolMode;
use App\Shared\Application\Assistant\ToolResult;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Security\Application\Account\AccountAuthorizerMother;
use App\Tests\Unit\Security\Domain\Model\Account\AccountMother;
use App\Tests\Unit\Security\Domain\Model\Account\AccountRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ListAccountsToolTest extends TestCase
{
    private InMemoryAccountRepository $accounts;

    private ListAccountsTool $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->accounts = AccountRepositoryMother::inMemory();
        $authorizer = AccountAuthorizerMother::create();
        $this->sut = new ListAccountsTool(new ListAccountsService($this->accounts, $authorizer), $authorizer);
    }

    public function testIsAReadToolAvailableOnlyToAdmins(): void
    {
        $this->assertSame('list_accounts', $this->sut->name());
        $this->assertSame(ToolMode::Read, $this->sut->mode());
        $this->assertTrue($this->sut->isAvailableTo(UserMother::adminUser()));
        $this->assertFalse($this->sut->isAvailableTo(UserMother::registeredUser()));
    }

    public function testListsTheAccountsWithTheirData(): void
    {
        $acme = AccountMother::fromName('Acme');
        $this->accounts->add($acme);

        $result = $this->sut->execute($this->arguments([]), UserMother::adminUser(), $this->context());

        $this->assertSame(1, $result->data['total']);
        $this->assertSame([[
            'id' => (string) $acme->id(),
            'href' => 'route:admin_accounts_edit?accountId=' . $acme->id(),
            'name' => 'Acme',
            'active' => true,
            'registeredAt' => $this->context()->dateTime($acme->registeredAt()),
        ]], $result->data['accounts']);
        $this->assertSame('admin_accounts', $result->link?->route);
    }

    public function testFiltersByNameAndStatus(): void
    {
        $this->accounts->add(AccountMother::fromName('Acme'));
        $globex = AccountMother::fromName('Globex');
        $globex->changeStatus(false);
        $this->accounts->add($globex);
        $this->accounts->add(AccountMother::fromName('Acme Labs'));

        $byName = $this->sut->execute($this->arguments(['name' => 'acme']), UserMother::adminUser(), $this->context());
        $inactive = $this->sut->execute($this->arguments(['active' => false]), UserMother::adminUser(), $this->context());

        $this->assertSame(2, $byName->data['total']);
        $this->assertSame(1, $inactive->data['total']);
        $this->assertSame('Globex', $inactive->data['accounts'][0]['name']);
    }

    public function testReturnsAtMostTheMaximumNumberOfItems(): void
    {
        for ($i = 0; $i < ToolResult::ITEMS_MAX_COUNT + 5; ++$i) {
            $this->accounts->add(AccountMother::fromName(sprintf('Account %d', $i)));
        }

        $result = $this->sut->execute($this->arguments(['limit' => 50]), UserMother::adminUser(), $this->context());

        $this->assertCount(ToolResult::ITEMS_MAX_COUNT, $result->data['accounts']);
    }

    /** @param array<string, mixed> $raw */
    private function arguments(array $raw): ToolArguments
    {
        return ToolArgumentsFactory::create($this->sut->parameters(), $raw, $this->context());
    }

    private function context(): ToolContext
    {
        return new ToolContext(new \DateTimeImmutable('2026-11-16 10:00:00'), new \DateTimeZone('Europe/Madrid'));
    }
}
