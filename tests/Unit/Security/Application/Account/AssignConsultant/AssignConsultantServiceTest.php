<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Application\Account\AssignConsultant;

use App\Security\Application\Account\AssignConsultant\AssignConsultantRequest;
use App\Security\Application\Account\AssignConsultant\AssignConsultantService;
use App\Security\Application\Account\ConsultantPolicy;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountConsultantChanged;
use App\Security\Domain\Model\Account\AccountIsNotConsultantException;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryAccountRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Security\Application\Account\AccountAuthorizerMother;
use App\Tests\Unit\Security\Domain\Model\Account\AccountMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class AssignConsultantServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testUseCaseAssignsConsultant(): void
    {
        $repository = new InMemoryAccountRepository();

        $account = AccountMother::fromName('MyAccount');
        $consultant = AccountMother::consultantAccount();

        $repository->add($account);
        $repository->add($consultant);

        $sut = $this->createSUT($repository);

        $result = $sut(
            new AssignConsultantRequest($account->id(), $consultant->id()),
            UserMother::adminUser(),
        );

        $this->assertNotNull($result->consultantAccountId);
        $this->assertTrue($consultant->id()->equals($result->consultantAccountId));

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertTrue($lastEvent instanceof AccountConsultantChanged);
    }

    public function testUseCaseFailsWhenTargetAccountIsNotConsultant(): void
    {
        $this->expectException(AccountIsNotConsultantException::class);

        $repository = new InMemoryAccountRepository();

        $account = AccountMother::fromName('MyAccount');
        $other = AccountMother::fromName('OtherAccount');

        $repository->add($account);
        $repository->add($other);

        $sut = $this->createSUT($repository);

        $sut(
            new AssignConsultantRequest($account->id(), $other->id()),
            UserMother::adminUser(),
        );
    }

    public function testUnauthorizedAccess(): void
    {
        $this->expectException(UnauthorizedActionException::class);

        $repository = new InMemoryAccountRepository();

        $account = AccountMother::fromName('MyAccount');
        $consultant = AccountMother::consultantAccount();

        $repository->add($account);
        $repository->add($consultant);

        $sut = $this->createSUT($repository);

        $sut(
            new AssignConsultantRequest($account->id(), $consultant->id()),
            UserMother::ownerUser(),
        );
    }

    public function testIsHandle(): void
    {
        $repository = new InMemoryAccountRepository();

        $sut = $this->createSUT($repository);

        $account = AccountMother::fromName('MyAccount');

        $this->assertTrue($sut->isHandled(new AssignConsultantRequest($account->id())));
    }

    private function createSUT(InMemoryAccountRepository $repository): AssignConsultantService
    {
        $authorizer = AccountAuthorizerMother::create();

        return new AssignConsultantService($repository, $authorizer, new ConsultantPolicy($repository));
    }
}
