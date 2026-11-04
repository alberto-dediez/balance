<?php

declare(strict_types=1);

namespace App\Tests\Unit\EventOperations\Application\Event;

use App\EventOperations\Application\Event\EventAuthorizer;
use App\EventOperations\Domain\Model\Event\Event;
use App\EventOperations\Domain\Model\Event\EventId;
use App\Security\Application\Action;
use App\Security\Domain\Model\Account\AccountFeatures;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\EventOperations\Domain\Model\Event\EventMother;
use App\Tests\Unit\Security\Application\Account\AccountFeaturesPolicyMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Application\CurrentAccount\StubCurrentAccountIdProvider;
use PHPUnit\Framework\TestCase;

class EventAuthorizerTest extends TestCase
{
    public function testOwnerCanListEventsOfTheirAccount(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
        $subject = new Event(id: EventId::generate(), accountId: new AccountId('account-id-test'));

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    public function testOwnerCanRegisterEventForTheirAccount(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
        $subject = new Event(id: EventId::generate(), accountId: new AccountId('account-id-test'));

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Register, $user));
    }

    public function testOwnerCanEditEventOfTheirAccount(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
        $subject = EventMother::from(name: 'Test', id: (string) EventId::generate(), accountId: 'account-id-test');

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Edit, $user));
    }

    public function testOwnerCannotEditEventOfOtherAccount(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
        $subject = EventMother::from(name: 'Test', id: (string) EventId::generate(), accountId: 'other-account-id');

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::Edit, $user));
    }

    public function testOwnerCanRemoveEventOfTheirAccount(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
        $subject = EventMother::from(name: 'Test', id: (string) EventId::generate(), accountId: 'account-id-test');

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Remove, $user));
    }

    public function testOwnerWithoutAccountCannotList(): void
    {
        $user = UserMother::fromUsernameAndRole('owner', Role::Owner);
        $subject = new Event(EventId::generate());

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    public function testAdminCannotListEvents(): void
    {
        $user = UserMother::adminUser();
        $subject = new Event(id: EventId::generate(), accountId: new AccountId('account-id-test'));

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    public function testUserCannotListEvents(): void
    {
        $user = UserMother::fromUsernameAndRole('user', Role::User);
        $subject = new Event(EventId::generate());

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    public function testOwnerCannotListNorEditEventsWithoutEventsFeature(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
        $subject = EventMother::from(name: 'Test', id: (string) EventId::generate(), accountId: 'account-id-test');
        $sut = $this->createSUTWithFeatures(new AccountFeatures(events: false));

        foreach ([Action::List, Action::Register, Action::View, Action::Edit, Action::Remove, Action::Export] as $action) {
            $this->assertFalse($sut->canDoThat($subject, $action, $user));
        }
    }

    public function testOwnerCanListAndEditEventsWithEventsFeature(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
        $subject = EventMother::from(name: 'Test', id: (string) EventId::generate(), accountId: 'account-id-test');
        $sut = $this->createSUTWithFeatures(new AccountFeatures(compensation: false));

        $this->assertTrue($sut->canDoThat($subject, Action::List, $user));
        $this->assertTrue($sut->canDoThat($subject, Action::Edit, $user));
    }

    private function createSUT(): EventAuthorizer
    {
        return new EventAuthorizer();
    }

    private function createSUTWithFeatures(AccountFeatures $features): EventAuthorizer
    {
        return new EventAuthorizer(
            currentAccountIdProvider: new StubCurrentAccountIdProvider(new AccountId('account-id-test')),
            accountFeaturesPolicy: AccountFeaturesPolicyMother::forAccount(new AccountId('account-id-test'), $features),
        );
    }
}
