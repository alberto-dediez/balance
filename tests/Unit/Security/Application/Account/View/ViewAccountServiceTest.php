<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Application\Account\View;

use App\Security\Application\Account\View\ViewAccountRequest;
use App\Security\Application\Account\View\ViewAccountService;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryAccountRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Security\Application\Account\AccountAuthorizerMother;
use App\Tests\Unit\Security\Domain\Model\Account\AccountMother;
use App\Tests\Unit\Security\Domain\Model\Account\AccountRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ViewAccountServiceTest extends TestCase
{
    private InMemoryAccountRepository $repository;

    private ViewAccountService $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->repository = AccountRepositoryMother::inMemory();
        $this->sut = new ViewAccountService($this->repository, AccountAuthorizerMother::create());
    }

    public function testReturnsTheAccount(): void
    {
        $account = AccountMother::fromName('Acme');
        $this->repository->add($account);

        $response = ($this->sut)(new ViewAccountRequest($account->id()), UserMother::adminUser());

        $this->assertTrue($response->account->id()->equals($account->id()));
        $this->assertSame('Acme', $response->account->name());
    }

    public function testFailsWhenTheAccountDoesNotExist(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ($this->sut)(new ViewAccountRequest(AccountId::generate()), UserMother::adminUser());
    }

    public function testAUserCannotViewAnAccount(): void
    {
        $account = AccountMother::fromName('Acme');
        $this->repository->add($account);

        $this->expectException(UnauthorizedActionException::class);

        ($this->sut)(new ViewAccountRequest($account->id()), UserMother::registeredUser());
    }
}
