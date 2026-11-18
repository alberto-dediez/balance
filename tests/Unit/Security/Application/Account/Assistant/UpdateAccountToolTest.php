<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Application\Account\Assistant;

use App\Security\Application\Account\Assistant\UpdateAccountTool;
use App\Security\Application\Account\ConsultantPolicy;
use App\Security\Application\Account\Update\UpdateAccountService;
use App\Security\Application\Account\View\ViewAccountService;
use App\Security\Domain\Model\Account\Account;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryAccountRepository;
use App\Shared\Application\Assistant\InvalidToolArgumentsException;
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

class UpdateAccountToolTest extends TestCase
{
    private InMemoryAccountRepository $accounts;

    private Account $account;

    private UpdateAccountTool $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->accounts = AccountRepositoryMother::inMemory();
        $this->account = AccountMother::withApiTokensEnabled('Acme', (string) AccountId::generate());
        $this->accounts->add($this->account);

        $authorizer = AccountAuthorizerMother::create();
        $this->sut = new UpdateAccountTool(
            new ViewAccountService($this->accounts, $authorizer),
            new UpdateAccountService($this->accounts, $authorizer, new ConsultantPolicy($this->accounts)),
            $this->accounts,
            $authorizer,
        );
    }

    public function testIsAWriteToolAvailableOnlyToAdmins(): void
    {
        $this->assertSame('update_account', $this->sut->name());
        $this->assertSame(ToolMode::Write, $this->sut->mode());
        $this->assertTrue($this->sut->isAvailableTo(UserMother::adminUser()));
        $this->assertFalse($this->sut->isAvailableTo(UserMother::registeredUser()));
    }

    public function testRenamesTheAccountKeepingItsStatusAndApiTokens(): void
    {
        $result = $this->sut->execute($this->arguments(['accountId' => (string) $this->account->id(), 'name' => 'Acme SL']), UserMother::adminUser(), $this->context());

        $this->assertSame('Acme SL', $this->account->name());
        $this->assertTrue($this->account->isActive());
        $this->assertTrue($this->account->allowsApiTokens());
        $this->assertSame(['accountId' => (string) $this->account->id()], $result->references);
        $this->assertSame('admin_accounts_edit', $result->link?->route);
    }

    public function testDeactivatesTheAccountKeepingItsName(): void
    {
        $this->sut->execute($this->arguments(['accountId' => (string) $this->account->id(), 'active' => false]), UserMother::adminUser(), $this->context());

        $this->assertSame('Acme', $this->account->name());
        $this->assertFalse($this->account->isActive());
        $this->assertTrue($this->account->allowsApiTokens());
    }

    public function testRequiresSomeField(): void
    {
        $this->expectException(InvalidToolArgumentsException::class);

        $this->sut->execute($this->arguments(['accountId' => (string) $this->account->id()]), UserMother::adminUser(), $this->context());
    }

    public function testThePreviewShowsTheAccountAndWarnsWhenItIsDeactivated(): void
    {
        $preview = $this->sut->preview($this->arguments(['accountId' => (string) $this->account->id(), 'active' => false]), UserMother::adminUser(), $this->context());

        $this->assertSame('Acme', $preview->displays['accountId']);
        $this->assertArrayHasKey('active', $preview->displays);
    }

    public function testThePreviewRejectsAnUnknownAccount(): void
    {
        $this->expectException(InvalidToolArgumentsException::class);

        $this->sut->preview($this->arguments(['accountId' => (string) AccountId::generate(), 'name' => 'Otra']), UserMother::adminUser(), $this->context());
    }

    public function testFailsOnAnUnknownAccount(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->sut->execute($this->arguments(['accountId' => (string) AccountId::generate(), 'name' => 'Otra']), UserMother::adminUser(), $this->context());
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
