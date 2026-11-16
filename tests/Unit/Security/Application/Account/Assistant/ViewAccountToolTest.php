<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Application\Account\Assistant;

use App\Security\Application\Account\Assistant\ViewAccountTool;
use App\Security\Application\Account\View\ViewAccountService;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryAccountRepository;
use App\Shared\Application\Assistant\ToolArguments;
use App\Shared\Application\Assistant\ToolArgumentsFactory;
use App\Shared\Application\Assistant\ToolContext;
use App\Shared\Application\Assistant\ToolMode;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Security\Application\Account\AccountAuthorizerMother;
use App\Tests\Unit\Security\Domain\Model\Account\AccountMother;
use App\Tests\Unit\Security\Domain\Model\Account\AccountRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ViewAccountToolTest extends TestCase
{
    private InMemoryAccountRepository $accounts;

    private ViewAccountTool $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->accounts = AccountRepositoryMother::inMemory();
        $authorizer = AccountAuthorizerMother::create();
        $this->sut = new ViewAccountTool(new ViewAccountService($this->accounts, $authorizer), $authorizer);
    }

    public function testIsAReadToolAvailableOnlyToAdmins(): void
    {
        $this->assertSame('view_account', $this->sut->name());
        $this->assertSame(ToolMode::Read, $this->sut->mode());
        $this->assertTrue($this->sut->isAvailableTo(UserMother::adminUser()));
        $this->assertFalse($this->sut->isAvailableTo(UserMother::registeredUser()));
    }

    public function testReturnsTheAccountDetail(): void
    {
        $account = AccountMother::withApiTokensEnabled('Acme', (string) AccountId::generate());
        $this->accounts->add($account);

        $result = $this->sut->execute($this->arguments(['accountId' => (string) $account->id()]), UserMother::adminUser(), $this->context());

        $this->assertSame([
            'id' => (string) $account->id(),
            'name' => 'Acme',
            'active' => true,
            'apiTokensEnabled' => true,
            'registeredAt' => $this->context()->dateTime($account->registeredAt()),
        ], $result->data);
        $this->assertSame(['accountId' => (string) $account->id()], $result->references);
        $this->assertSame('admin_accounts_edit', $result->link?->route);
    }

    public function testFailsWhenTheAccountDoesNotExist(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->sut->execute($this->arguments(['accountId' => (string) AccountId::generate()]), UserMother::adminUser(), $this->context());
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
