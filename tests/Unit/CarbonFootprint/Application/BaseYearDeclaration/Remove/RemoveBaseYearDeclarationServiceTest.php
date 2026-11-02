<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\BaseYearDeclaration\Remove;

use App\CarbonFootprint\Application\BaseYearDeclaration\BaseYearDeclarationAuthorizer;
use App\CarbonFootprint\Application\BaseYearDeclaration\Remove\RemoveBaseYearDeclarationRequest;
use App\CarbonFootprint\Application\BaseYearDeclaration\Remove\RemoveBaseYearDeclarationService;
use App\CarbonFootprint\Domain\Model\BaseYearDeclaration\BaseYearDeclarationId;
use App\CarbonFootprint\Domain\Model\BaseYearDeclaration\BaseYearDeclarationRemoved;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryBaseYearDeclarationRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\BaseYearDeclaration\BaseYearDeclarationMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\BaseYearDeclaration\BaseYearDeclarationRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

final class RemoveBaseYearDeclarationServiceTest extends TestCase
{
    private InMemoryBaseYearDeclarationRepository $repository;
    private RemoveBaseYearDeclarationService $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->repository = BaseYearDeclarationRepositoryMother::inMemory();
        $this->sut = new RemoveBaseYearDeclarationService($this->repository, new BaseYearDeclarationAuthorizer());
    }

    public function testOwnerRemovesTheDeclaration(): void
    {
        $spy = new SpyDomainEventSubscriber();
        $declaration = BaseYearDeclarationMother::declared();
        $this->repository->add($declaration);

        ($this->sut)(new RemoveBaseYearDeclarationRequest($declaration->id()), UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-1')));

        $this->assertNull($this->repository->find($declaration->id()));
        $this->assertInstanceOf(BaseYearDeclarationRemoved::class, $spy->lastEvent());
    }

    public function testOwnerOfAnotherAccountIsDenied(): void
    {
        $declaration = BaseYearDeclarationMother::declared();
        $this->repository->add($declaration);

        $this->expectException(UnauthorizedActionException::class);

        ($this->sut)(new RemoveBaseYearDeclarationRequest($declaration->id()), UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-2')));
    }

    public function testNotFoundThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ($this->sut)(new RemoveBaseYearDeclarationRequest(BaseYearDeclarationId::generate()), UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-1')));
    }
}
