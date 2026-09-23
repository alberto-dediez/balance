<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Application\Account\Update;

use App\Security\Application\Account\ConsultantPolicy;
use App\Security\Application\Account\Update\UpdateAccountRequest;
use App\Security\Application\Account\Update\UpdateAccountService;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountConsultantChanged;
use App\Security\Domain\Model\Account\AccountConsultantStatusChanged;
use App\Security\Domain\Model\Account\AccountEmissionFactorGroupChanged;
use App\Security\Domain\Model\Account\AccountNameChanged;
use App\Security\Domain\Model\Account\AccountReportPermissionsChanged;
use App\Security\Domain\Model\Account\AccountStatusChanged;
use App\Security\Domain\Model\Account\ConsultantCannotHaveConsultantException;
use App\Security\Domain\Model\Account\ConsultantHasAssignedAccountsException;
use App\Security\Domain\Model\User\Role;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryAccountRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\Security\Application\Account\AccountAuthorizerMother;
use App\Tests\Unit\Security\Domain\Model\Account\AccountMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class UpdateAccountServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testUseCaseChangeNameAndState(): void
    {
        $repository = new InMemoryAccountRepository();

        $account = AccountMother::fromName('MyAccount');

        $user = UserMother::adminUser();

        $repository->add($account);

        $sut = $this->createSUT($repository);

        $request = new UpdateAccountRequest(
            accountId: $account->id(),
            name: 'Company ABC',
            active: false,
        );

        $result = $sut($request, $user);

        $accountPersisted = $repository->find($result->accountId);

        $this->assertNotNull($accountPersisted);

        $events = $this->spySubscriber->events();

        $this->assertGreaterThanOrEqual(2, count($events));

        $eventFirst = $events[count($events) - 2];
        $eventLast = $events[count($events) - 1];

        $this->assertTrue($eventFirst instanceof AccountNameChanged);
        $this->assertTrue($eventLast instanceof AccountStatusChanged);
    }

    public function testUseWhenOnlyChangeName(): void
    {
        $repository = new InMemoryAccountRepository();

        $account = AccountMother::fromName('MyAccount');

        $user = UserMother::adminUser();

        $repository->add($account);

        $sut = $this->createSUT($repository);

        $request = new UpdateAccountRequest(
            accountId: $account->id(),
            name: 'Company ABC',
            active: true,
        );

        $result = $sut($request, $user);

        $accountPersisted = $repository->find($result->accountId);

        $this->assertNotNull($accountPersisted);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof AccountNameChanged);
        $this->assertEquals('Company ABC', $lastEvent->name());
        $this->assertTrue($accountPersisted->id()->equals($lastEvent->accountId()));
    }

    public function testUseWhenNothingChange(): void
    {
        $repository = new InMemoryAccountRepository();

        $account = AccountMother::fromName('MyAccount');

        $user = UserMother::adminUser();

        $repository->add($account);

        $sut = $this->createSUT($repository);

        $request = new UpdateAccountRequest(
            accountId: $account->id(),
            name: 'MyAccount',
            active: true,
        );

        $result = $sut($request, $user);

        $accountPersisted = $repository->find($result->accountId);

        $this->assertNotNull($accountPersisted);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertFalse($lastEvent instanceof AccountNameChanged);
        $this->assertFalse($lastEvent instanceof AccountStatusChanged);
    }

    public function testUseCaseChangeEmissionFactorGroup(): void
    {
        $repository = new InMemoryAccountRepository();

        $account = AccountMother::fromName('MyAccount');

        $user = UserMother::adminUser();

        $repository->add($account);

        $sut = $this->createSUT($repository);

        $newEmissionFactorGroupId = EmissionFactorGroupMother::random()->id();

        $request = new UpdateAccountRequest(
            accountId: $account->id(),
            name: $account->name(),
            active: $account->isActive(),
            emissionFactorGroupId: $newEmissionFactorGroupId,
        );

        $result = $sut($request, $user);

        $accountPersisted = $repository->find($result->accountId);

        $this->assertNotNull($accountPersisted);
        $this->assertNotNull($accountPersisted->emissionFactorGroupId());
        $this->assertTrue($newEmissionFactorGroupId->equals($accountPersisted->emissionFactorGroupId()));

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertTrue($lastEvent instanceof AccountEmissionFactorGroupChanged);
        $this->assertTrue($newEmissionFactorGroupId->equals($lastEvent->emissionFactorGroupId()));
    }

    public function testUseCaseAssignsConsultant(): void
    {
        $repository = new InMemoryAccountRepository();

        $account = AccountMother::fromName('MyAccount');
        $consultant = AccountMother::consultantAccount();

        $user = UserMother::adminUser();

        $repository->add($account);
        $repository->add($consultant);

        $sut = $this->createSUT($repository);

        $request = new UpdateAccountRequest(
            accountId: $account->id(),
            name: $account->name(),
            active: $account->isActive(),
            consultantAccountId: $consultant->id(),
        );

        $result = $sut($request, $user);

        $accountPersisted = $repository->find($result->accountId);

        $this->assertNotNull($accountPersisted);
        $this->assertNotNull($accountPersisted->consultantAccountId());
        $this->assertTrue($consultant->id()->equals($accountPersisted->consultantAccountId()));

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertTrue($lastEvent instanceof AccountConsultantChanged);
    }

    public function testUseCaseUnassignsConsultant(): void
    {
        $repository = new InMemoryAccountRepository();

        $consultant = AccountMother::consultantAccount();
        $account = AccountMother::fromNameWithConsultant('MyAccount', $consultant->id());

        $user = UserMother::adminUser();

        $repository->add($account);
        $repository->add($consultant);

        $sut = $this->createSUT($repository);

        $request = new UpdateAccountRequest(
            accountId: $account->id(),
            name: $account->name(),
            active: $account->isActive(),
        );

        $result = $sut($request, $user);

        $accountPersisted = $repository->find($result->accountId);

        $this->assertNotNull($accountPersisted);
        $this->assertNull($accountPersisted->consultantAccountId());

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertTrue($lastEvent instanceof AccountConsultantChanged);
        $this->assertNull($lastEvent->consultantAccountId());
    }

    public function testUseCaseMarksAccountAsConsultant(): void
    {
        $repository = new InMemoryAccountRepository();

        $account = AccountMother::fromName('MyAccount');

        $user = UserMother::adminUser();

        $repository->add($account);

        $sut = $this->createSUT($repository);

        $request = new UpdateAccountRequest(
            accountId: $account->id(),
            name: $account->name(),
            active: $account->isActive(),
            consultant: true,
        );

        $result = $sut($request, $user);

        $accountPersisted = $repository->find($result->accountId);

        $this->assertNotNull($accountPersisted);
        $this->assertTrue($accountPersisted->isConsultant());

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertTrue($lastEvent instanceof AccountConsultantStatusChanged);
        $this->assertTrue($lastEvent->consultant());
    }

    public function testUseCaseMarksAsConsultantWhileReleasingItsOwnConsultant(): void
    {
        $repository = new InMemoryAccountRepository();

        $consultant = AccountMother::consultantAccount();
        $account = AccountMother::fromNameWithConsultant('MyAccount', $consultant->id());

        $repository->add($consultant);
        $repository->add($account);

        $user = UserMother::adminUser();

        $sut = $this->createSUT($repository);

        $request = new UpdateAccountRequest(
            accountId: $account->id(),
            name: $account->name(),
            active: $account->isActive(),
            consultant: true,
        );

        $result = $sut($request, $user);

        $accountPersisted = $repository->find($result->accountId);

        $this->assertNotNull($accountPersisted);
        $this->assertTrue($accountPersisted->isConsultant());
        $this->assertNull($accountPersisted->consultantAccountId());
    }

    public function testUseCaseCannotMarkAsConsultantAnAccountThatKeepsItsConsultant(): void
    {
        $this->expectException(ConsultantCannotHaveConsultantException::class);

        $repository = new InMemoryAccountRepository();

        $consultant = AccountMother::consultantAccount();
        $account = AccountMother::fromNameWithConsultant('MyAccount', $consultant->id());

        $repository->add($consultant);
        $repository->add($account);

        $user = UserMother::adminUser();

        $sut = $this->createSUT($repository);

        $request = new UpdateAccountRequest(
            accountId: $account->id(),
            name: $account->name(),
            active: $account->isActive(),
            consultant: true,
            consultantAccountId: $consultant->id(),
        );

        $sut($request, $user);
    }

    public function testUseCaseCannotUnmarkConsultantWithAssignedAccounts(): void
    {
        $this->expectException(ConsultantHasAssignedAccountsException::class);

        $repository = new InMemoryAccountRepository();

        $consultant = AccountMother::consultantAccount();

        $repository->add($consultant);
        $repository->add(AccountMother::fromNameWithConsultant('MyAccount', $consultant->id()));

        $user = UserMother::adminUser();

        $sut = $this->createSUT($repository);

        $request = new UpdateAccountRequest(
            accountId: $consultant->id(),
            name: $consultant->name(),
            active: $consultant->isActive(),
        );

        $sut($request, $user);
    }

    public function testUnauthorizedAccess(): void
    {
        $this->expectException(UnauthorizedActionException::class);

        $repository = new InMemoryAccountRepository();

        $account = AccountMother::fromName('MyAccount');

        $user = UserMother::ownerUser();

        $repository->add($account);

        $sut = $this->createSUT($repository);

        $request = new UpdateAccountRequest(
            accountId: $account->id(),
            name: 'MyAccount',
            active: true,
        );

        $sut($request, $user);
    }

    public function testAdminChangesTheReportPermissions(): void
    {
        $repository = new InMemoryAccountRepository();

        $account = AccountMother::fromName('MyAccount');

        $repository->add($account);

        $sut = $this->createSUT($repository);

        $request = new UpdateAccountRequest(
            accountId: $account->id(),
            name: $account->name(),
            active: $account->isActive(),
            reportCanEditTexts: true,
            reportAutoReview: false,
        );

        $sut($request, UserMother::adminUser());

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertInstanceOf(AccountReportPermissionsChanged::class, $lastEvent);
        $this->assertTrue($account->reportPermissions()->canEditTexts());
        $this->assertFalse($account->reportPermissions()->autoReview());
    }

    public function testOwnerCannotChangeTheReportPermissionsOfItsOwnAccount(): void
    {
        $this->expectException(UnauthorizedActionException::class);

        $repository = new InMemoryAccountRepository();

        $account = AccountMother::fromName('MyAccount');

        $repository->add($account);

        $sut = $this->createSUT($repository);

        $request = new UpdateAccountRequest(
            accountId: $account->id(),
            name: $account->name(),
            active: $account->isActive(),
            reportCanEditTexts: true,
            reportAutoReview: true,
        );

        $sut($request, UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $account->id()));
    }

    public function testOwnerCanRenameItsOwnAccountWithoutChangingTheReportPermissions(): void
    {
        $repository = new InMemoryAccountRepository();

        $account = AccountMother::fromName('MyAccount');

        $repository->add($account);

        $sut = $this->createSUT($repository);

        $request = new UpdateAccountRequest(
            accountId: $account->id(),
            name: 'Renamed',
            active: $account->isActive(),
        );

        $sut($request, UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $account->id()));

        $this->assertSame('Renamed', $account->name());
    }

    public function testIsHandle(): void
    {
        $repository = new InMemoryAccountRepository();

        $sut = $this->createSUT($repository);

        $account = AccountMother::fromName('MyAccount');

        $result = $sut->isHandled(new UpdateAccountRequest(
            accountId: $account->id(),
            name: 'MyAccount',
            active: true,
        ));

        $this->assertTrue($result);
    }

    private function createSUT(InMemoryAccountRepository $repository): UpdateAccountService
    {
        $authorizer = AccountAuthorizerMother::create();

        return new UpdateAccountService($repository, $authorizer, new ConsultantPolicy($repository));
    }
}
