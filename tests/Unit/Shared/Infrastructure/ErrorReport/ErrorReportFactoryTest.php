<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\ErrorReport;

use App\Security\Domain\Model\Account\AccountRepository;
use App\Security\Domain\Model\User\Role;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Application\CurrentAccount\CurrentAccountIdProvider;
use App\Shared\Application\CurrentRoles\CurrentRolesProvider;
use App\Shared\Domain\Model\ErrorReport\ErrorReportRequest;
use App\Shared\Infrastructure\ErrorReport\ErrorReportFactory;
use App\Tests\Unit\Security\Domain\Model\Account\AccountMother;
use App\Tests\Unit\Security\Domain\Model\Account\AccountRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Application\CurrentAccount\StubCurrentAccountIdProvider;
use App\Tests\Unit\Shared\Application\CurrentRoles\StubCurrentRolesProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class ErrorReportFactoryTest extends TestCase
{
    public function testItBuildsAReportForAnAnonymousRequest(): void
    {
        $sut = $this->createSUT();

        $report = $sut->fromRequest(new \RuntimeException('boom'), 500, Request::create('/es/waste?page=2'));

        $this->assertSame(500, $report->statusCode);
        $this->assertSame(\RuntimeException::class, $report->exception->class);
        $this->assertSame('boom', $report->exception->message);
        $this->assertSame('test', $report->environment);
        $this->assertNotEmpty($report->incidentId);
        $this->assertNull($report->context->userId);
        $this->assertInstanceOf(ErrorReportRequest::class, $report->request);
        $this->assertSame('GET', $report->request->method);
        $this->assertSame(['page' => '2'], $report->request->query);
    }

    public function testItRedactsSensitiveParameters(): void
    {
        $sut = $this->createSUT();

        $request = Request::create('/es/login', 'POST', [
            'username' => 'user@email.com',
            'password' => 'secret-value',
            'form' => ['_csrf_token' => 'a-token', 'name' => 'a name'],
        ]);

        $report = $sut->fromRequest(new \RuntimeException('boom'), 500, $request);

        $this->assertInstanceOf(ErrorReportRequest::class, $report->request);
        $this->assertSame('user@email.com', $report->request->body['username']);
        $this->assertSame('***', $report->request->body['password']);
        $this->assertSame('***', $report->request->body['form[_csrf_token]']);
        $this->assertSame('a name', $report->request->body['form[name]']);
    }

    public function testItReportsTheImpersonatedAccountAsCurrentAccount(): void
    {
        $ownerAccount = AccountMother::fromName('propietaria');
        $impersonatedAccount = AccountMother::fromName('impersonada');

        $accountRepository = AccountRepositoryMother::inMemory();
        $accountRepository->add($ownerAccount);
        $accountRepository->add($impersonatedAccount);

        $user = UserMother::fromUsernameAndRoleAndAccount('admin@email.com', Role::Admin, $ownerAccount->id());

        $sut = $this->createSUT(
            loggedUser: new StubLoggedUser($user),
            currentAccountIdProvider: new StubCurrentAccountIdProvider($impersonatedAccount->id()),
            currentRolesProvider: new StubCurrentRolesProvider([Role::Owner, Role::User]),
            accountRepository: $accountRepository,
        );

        $report = $sut->fromRequest(new \RuntimeException('boom'), 500, Request::create('/es/waste'));

        $this->assertSame('admin@email.com', $report->context->username);
        $this->assertTrue($report->context->impersonated);
        $this->assertSame($impersonatedAccount->id()->id(), $report->context->currentAccountId);
        $this->assertSame('impersonada', $report->context->currentAccountName);
        $this->assertSame($ownerAccount->id()->id(), $report->context->ownerAccountId);
        $this->assertSame(['ROLE_OWNER', 'ROLE_USER'], $report->context->roles);
    }

    public function testItUsesTheUserRolesWhenThereIsNoImpersonation(): void
    {
        $account = AccountMother::fromName('propietaria');

        $accountRepository = AccountRepositoryMother::inMemory();
        $accountRepository->add($account);

        $user = UserMother::fromUsernameAndRoleAndAccount('admin@email.com', Role::Admin, $account->id());

        $sut = $this->createSUT(
            loggedUser: new StubLoggedUser($user),
            currentAccountIdProvider: new StubCurrentAccountIdProvider($account->id()),
            accountRepository: $accountRepository,
        );

        $report = $sut->fromRequest(new \RuntimeException('boom'), 500, Request::create('/es/waste'));

        $this->assertFalse($report->context->impersonated);
        $this->assertSame(['ROLE_ADMIN', 'ROLE_USER'], $report->context->roles);
    }

    public function testItSharesTheFingerprintForTheSameError(): void
    {
        $sut = $this->createSUT();

        $error = new \RuntimeException('boom');

        $first = $sut->fromRequest($error, 500, Request::create('/es/waste'));
        $second = $sut->fromRequest($error, 500, Request::create('/es/other'));
        $another = $sut->fromRequest(new \LogicException('boom'), 500, Request::create('/es/waste'));

        $this->assertSame($first->fingerprint, $second->fingerprint);
        $this->assertNotSame($first->fingerprint, $another->fingerprint);
        $this->assertNotSame($first->incidentId, $second->incidentId);
    }

    public function testItCollectsPreviousExceptions(): void
    {
        $sut = $this->createSUT();

        $error = new \RuntimeException('outer', 0, new \LogicException('inner'));

        $report = $sut->fromRequest($error, 500, null);

        $this->assertNull($report->request);
        $this->assertCount(1, $report->previous);
        $this->assertSame('inner', $report->previous[0]->message);
    }

    public function testItBuildsAReportForAConsoleCommand(): void
    {
        $sut = $this->createSUT();

        $report = $sut->fromCommand(new \RuntimeException('boom'), 1, 'app:do-something');

        $this->assertSame('app:do-something', $report->command);
        $this->assertSame(1, $report->statusCode);
        $this->assertNull($report->request);
    }

    private function createSUT(
        ?LoggedUser $loggedUser = null,
        ?CurrentAccountIdProvider $currentAccountIdProvider = null,
        ?CurrentRolesProvider $currentRolesProvider = null,
        ?AccountRepository $accountRepository = null,
    ): ErrorReportFactory {
        return new ErrorReportFactory(
            loggedUser: $loggedUser ?? new StubLoggedUser(),
            currentAccountIdProvider: $currentAccountIdProvider ?? new StubCurrentAccountIdProvider(),
            currentRolesProvider: $currentRolesProvider ?? new StubCurrentRolesProvider(),
            accountRepository: $accountRepository ?? AccountRepositoryMother::inMemory(),
            environment: 'test',
        );
    }
}
