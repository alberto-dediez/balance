<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Infrastructure\Symfony\Security;

use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\UserId;
use App\Security\Infrastructure\Symfony\Security\ImpersonatedAccount;
use App\Tests\Unit\Security\Domain\Model\Account\AccountMother;
use App\Tests\Unit\Security\Domain\Model\Account\AccountRepositoryMother;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

class ImpersonatedAccountTest extends TestCase
{
    public function testDisabledFlagNeverReturnsAnAccount(): void
    {
        $requestStack = $this->buildRequestStack();
        $repository = AccountRepositoryMother::inMemory();

        $sut = new ImpersonatedAccount($requestStack, $repository, false);

        $this->assertNull($sut->account());
        $this->assertNull($sut->accountId());
        $this->assertNull($sut->impersonatorId());
        $this->assertFalse($sut->isImpersonating());

        $sut->impersonate(AccountId::generate(), UserId::generate());

        $this->assertNull($sut->account());
        $this->assertFalse($sut->isImpersonating());
    }

    public function testImpersonateStoresAndResolvesTheAccountAndImpersonator(): void
    {
        $requestStack = $this->buildRequestStack();
        $repository = AccountRepositoryMother::inMemory();

        $account = AccountMother::fromName('Acme');
        $repository->add($account);

        $impersonatorId = UserId::generate();

        $sut = new ImpersonatedAccount($requestStack, $repository, true);

        $this->assertFalse($sut->isImpersonating());
        $this->assertNull($sut->impersonatorId());

        $sut->impersonate($account->id(), $impersonatorId);

        $this->assertTrue($sut->isImpersonating());
        $this->assertSame((string) $account->id(), (string) $sut->accountId());
        $this->assertSame('Acme', (string) $sut->account());
        $this->assertSame((string) $impersonatorId, (string) $sut->impersonatorId());
    }

    public function testAccountIdIsNullWhenTheAccountNoLongerExists(): void
    {
        $requestStack = $this->buildRequestStack();
        $repository = AccountRepositoryMother::inMemory();

        $account = AccountMother::fromName('Acme');
        $repository->add($account);

        $sut = new ImpersonatedAccount($requestStack, $repository, true);
        $sut->impersonate($account->id(), UserId::generate());

        $this->assertTrue($sut->isImpersonating());

        $repository->remove($account);
        $sut->reset();

        $this->assertNull($sut->account());
        $this->assertNull($sut->accountId());
        $this->assertFalse($sut->isImpersonating());
    }

    public function testExitClearsTheImpersonation(): void
    {
        $requestStack = $this->buildRequestStack();
        $repository = AccountRepositoryMother::inMemory();

        $account = AccountMother::fromName('Acme');
        $repository->add($account);

        $sut = new ImpersonatedAccount($requestStack, $repository, true);
        $sut->impersonate($account->id(), UserId::generate());

        $this->assertTrue($sut->isImpersonating());

        $sut->exit();

        $this->assertFalse($sut->isImpersonating());
        $this->assertNull($sut->account());
        $this->assertNull($sut->accountId());
        $this->assertNull($sut->impersonatorId());
    }

    public function testResetForgetsTheCachedAccount(): void
    {
        $requestStack = $this->buildRequestStack();
        $repository = AccountRepositoryMother::inMemory();

        $account = AccountMother::fromName('Acme');
        $repository->add($account);

        $sut = new ImpersonatedAccount($requestStack, $repository, true);
        $sut->impersonate($account->id(), UserId::generate());

        $this->assertTrue($sut->isImpersonating());

        $sut->reset();

        $this->assertTrue($sut->isImpersonating());
    }

    private function buildRequestStack(): RequestStack
    {
        $request = Request::create('/');
        $request->setSession(new Session(new MockArraySessionStorage()));

        $requestStack = new RequestStack();
        $requestStack->push($request);

        return $requestStack;
    }
}
