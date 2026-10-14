<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Application\Account\ChangeConsultantStatus;

use App\Security\Application\Account\ChangeConsultantStatus\ChangeConsultantStatusRequest;
use App\Security\Application\Account\ChangeConsultantStatus\ChangeConsultantStatusService;
use App\Security\Application\Account\ConsultantPolicy;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountConsultantStatusChanged;
use App\Security\Domain\Model\Account\ConsultantHasAssignedAccountsException;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryAccountRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Security\Application\Account\AccountAuthorizerMother;
use App\Tests\Unit\Security\Domain\Model\Account\AccountMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class ChangeConsultantStatusServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testUseCaseMarksAccountAsConsultant(): void
    {
        $repository = new InMemoryAccountRepository();

        $account = AccountMother::fromName('MyAccount');

        $repository->add($account);

        $sut = $this->createSUT($repository);

        $result = $sut(
            new ChangeConsultantStatusRequest($account->id(), true),
            UserMother::adminUser(),
        );

        $this->assertTrue($result->consultant);
        $this->assertTrue($account->isConsultant());
        $this->assertTrue($this->spySubscriber->lastEvent() instanceof AccountConsultantStatusChanged);
    }

    public function testUseCaseUnmarksAccountAsConsultant(): void
    {
        $repository = new InMemoryAccountRepository();

        $consultant = AccountMother::consultantAccount();

        $repository->add($consultant);

        $sut = $this->createSUT($repository);

        $result = $sut(
            new ChangeConsultantStatusRequest($consultant->id(), false),
            UserMother::adminUser(),
        );

        $this->assertFalse($result->consultant);
        $this->assertFalse($consultant->isConsultant());
    }

    public function testUseCaseDoesNothingWhenStatusDoesNotChange(): void
    {
        $repository = new InMemoryAccountRepository();

        $consultant = AccountMother::consultantAccount();

        $repository->add($consultant);

        $sut = $this->createSUT($repository);

        $result = $sut(
            new ChangeConsultantStatusRequest($consultant->id(), true),
            UserMother::adminUser(),
        );

        $this->assertTrue($result->consultant);
        $this->assertFalse($this->spySubscriber->lastEvent() instanceof AccountConsultantStatusChanged);
    }

    public function testUseCaseFailsWhenConsultantHasAssignedAccounts(): void
    {
        $this->expectException(ConsultantHasAssignedAccountsException::class);

        $repository = new InMemoryAccountRepository();

        $consultant = AccountMother::consultantAccount();
        $assigned = AccountMother::fromNameWithConsultant('MyAccount', $consultant->id());

        $repository->add($consultant);
        $repository->add($assigned);

        $sut = $this->createSUT($repository);

        $sut(
            new ChangeConsultantStatusRequest($consultant->id(), false),
            UserMother::adminUser(),
        );
    }

    public function testUnauthorizedAccess(): void
    {
        $this->expectException(UnauthorizedActionException::class);

        $repository = new InMemoryAccountRepository();

        $account = AccountMother::fromName('MyAccount');

        $repository->add($account);

        $sut = $this->createSUT($repository);

        $sut(
            new ChangeConsultantStatusRequest($account->id(), true),
            UserMother::ownerUser(),
        );
    }

    public function testIsHandle(): void
    {
        $repository = new InMemoryAccountRepository();

        $sut = $this->createSUT($repository);

        $account = AccountMother::fromName('MyAccount');

        $this->assertTrue($sut->isHandled(new ChangeConsultantStatusRequest($account->id())));
    }

    private function createSUT(InMemoryAccountRepository $repository): ChangeConsultantStatusService
    {
        $authorizer = AccountAuthorizerMother::create();

        return new ChangeConsultantStatusService($repository, $authorizer, new ConsultantPolicy($repository));
    }
}
