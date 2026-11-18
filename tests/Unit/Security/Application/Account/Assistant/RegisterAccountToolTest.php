<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Application\Account\Assistant;

use App\Security\Application\Account\Assistant\RegisterAccountTool;
use App\Security\Application\Account\Register\RegisterAccountService;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryAccountRepository;
use App\Shared\Application\Assistant\InvalidToolArgumentsException;
use App\Shared\Application\Assistant\ToolArguments;
use App\Shared\Application\Assistant\ToolArgumentsFactory;
use App\Shared\Application\Assistant\ToolContext;
use App\Shared\Application\Assistant\ToolMode;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Security\Application\Account\AccountAuthorizerMother;
use App\Tests\Unit\Security\Domain\Model\Account\AccountRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class RegisterAccountToolTest extends TestCase
{
    private InMemoryAccountRepository $accounts;

    private RegisterAccountTool $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->accounts = AccountRepositoryMother::inMemory();
        $authorizer = AccountAuthorizerMother::create();
        $this->sut = new RegisterAccountTool(new RegisterAccountService($this->accounts, $authorizer), $authorizer);
    }

    public function testIsAWriteToolAvailableOnlyToAdmins(): void
    {
        $this->assertSame('register_account', $this->sut->name());
        $this->assertSame(ToolMode::Write, $this->sut->mode());
        $this->assertTrue($this->sut->isAvailableTo(UserMother::adminUser()));
        $this->assertFalse($this->sut->isAvailableTo(UserMother::registeredUser()));

        try {
            $this->sut->preview($this->arguments(['name' => 'Acme', 'active' => true]), UserMother::registeredUser(), $this->context());
            $this->fail('A user must not preview the registration');
        } catch (InvalidToolArgumentsException) {
        }

        $this->expectException(UnauthorizedActionException::class);

        $this->sut->execute($this->arguments(['name' => 'Acme', 'active' => true]), UserMother::registeredUser(), $this->context());
    }

    public function testActiveIsRequired(): void
    {
        $this->expectException(InvalidToolArgumentsException::class);

        $this->arguments(['name' => 'Acme']);
    }

    public function testRegistersTheAccountAndReturnsItsReference(): void
    {
        $result = $this->sut->execute($this->arguments(['name' => 'Acme', 'active' => false]), UserMother::adminUser(), $this->context());

        $account = $this->accounts->find(new AccountId($result->references['accountId']));

        $this->assertNotNull($account);
        $this->assertSame('Acme', $account->name());
        $this->assertFalse($account->isActive());
        $this->assertSame('admin_accounts_edit', $result->link?->route);
        $this->assertSame(['accountId'], $result->link->parameters);
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
