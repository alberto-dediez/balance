<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Application\User\Assistant;

use App\Security\Application\Account\View\ViewAccountService;
use App\Security\Application\User\Assistant\ListUsersTool;
use App\Security\Application\User\List\ListUsersService;
use App\Security\Domain\Model\User\Role;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryAccountRepository;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryUserRepository;
use App\Shared\Application\Assistant\ToolArguments;
use App\Shared\Application\Assistant\ToolArgumentsFactory;
use App\Shared\Application\Assistant\ToolContext;
use App\Shared\Application\Assistant\ToolMode;
use App\Shared\Application\Assistant\ToolResult;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Security\Application\Account\AccountAuthorizerMother;
use App\Tests\Unit\Security\Application\User\UserAuthorizerMother;
use App\Tests\Unit\Security\Domain\Model\Account\AccountMother;
use App\Tests\Unit\Security\Domain\Model\Account\AccountRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Security\Domain\Model\User\UserRepositoryMother;
use PHPUnit\Framework\TestCase;

class ListUsersToolTest extends TestCase
{
    private InMemoryUserRepository $users;

    private InMemoryAccountRepository $accounts;

    private ListUsersTool $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->users = UserRepositoryMother::inMemory();
        $this->accounts = AccountRepositoryMother::inMemory();
        $authorizer = UserAuthorizerMother::create();
        $this->sut = new ListUsersTool(
            new ListUsersService($this->users, $authorizer),
            new ViewAccountService($this->accounts, AccountAuthorizerMother::create()),
            $authorizer,
        );
    }

    public function testIsAReadToolAvailableOnlyToAdmins(): void
    {
        $this->assertSame('list_users', $this->sut->name());
        $this->assertSame(ToolMode::Read, $this->sut->mode());
        $this->assertTrue($this->sut->isAvailableTo(UserMother::adminUser()));
        $this->assertFalse($this->sut->isAvailableTo(UserMother::registeredUser()));
    }

    public function testListsTheUsersWithTheirData(): void
    {
        $account = AccountMother::fromName('Acme');
        $this->accounts->add($account);
        $ana = UserMother::fromUsernameAndRoleAndAccount('ana@acme.test', Role::Owner, $account->id(), $account);
        $this->users->add($ana);

        $result = $this->sut->execute($this->arguments([]), UserMother::adminUser(), $this->context());

        $this->assertSame(1, $result->data['total']);
        $this->assertSame([[
            'id' => (string) $ana->id(),
            'username' => 'ana@acme.test',
            'roles' => ['ROLE_OWNER', 'ROLE_USER'],
            'account' => ['id' => (string) $account->id(), 'name' => 'Acme'],
            'active' => $ana->isActive(),
            'registeredAt' => $this->context()->dateTime($ana->registeredAt()),
            'twoFactor' => false,
        ]], $result->data['users']);
        $this->assertSame('admin_users', $result->link?->route);
    }

    public function testFiltersByUsernameRoleAndAccount(): void
    {
        $account = AccountMother::fromName('Acme');
        $this->accounts->add($account);
        $this->users->add(UserMother::fromUsernameAndRoleAndAccount('ana@acme.test', Role::Owner, $account->id()));
        $this->users->add(UserMother::fromUsernameAndRoleAndAccount('luis@acme.test', Role::User, $account->id()));
        $this->users->add(UserMother::fromUsernameAndRole('ana@globex.test', Role::Owner));

        $byUsername = $this->sut->execute($this->arguments(['username' => 'ana']), UserMother::adminUser(), $this->context());
        $byRoleAndAccount = $this->sut->execute($this->arguments(['role' => 'ROLE_OWNER', 'accountId' => (string) $account->id()]), UserMother::adminUser(), $this->context());

        $this->assertSame(2, $byUsername->data['total']);
        $this->assertSame(1, $byRoleAndAccount->data['total']);
        $this->assertSame('ana@acme.test', $byRoleAndAccount->data['users'][0]['username']);
    }

    public function testReturnsAtMostTheMaximumNumberOfItems(): void
    {
        for ($i = 0; $i < ToolResult::ITEMS_MAX_COUNT + 5; ++$i) {
            $this->users->add(UserMother::fromUsername(sprintf('user%d@acme.test', $i)));
        }

        $result = $this->sut->execute($this->arguments(['limit' => 50]), UserMother::adminUser(), $this->context());

        $this->assertCount(ToolResult::ITEMS_MAX_COUNT, $result->data['users']);
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
