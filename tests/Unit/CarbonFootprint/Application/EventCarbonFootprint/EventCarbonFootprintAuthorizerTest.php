<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\EventCarbonFootprint;

use App\CarbonFootprint\Application\EventCarbonFootprint\EventCarbonFootprintAuthorizer;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprint;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintId;
use App\EventOperations\Domain\Model\Event\EventId;
use App\Security\Application\Action;
use App\Security\Domain\Model\Account\AccountFeatures;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintMother;
use App\Tests\Unit\Security\Application\Account\AccountFeaturesPolicyMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Application\CurrentAccount\StubCurrentAccountIdProvider;
use PHPUnit\Framework\TestCase;

class EventCarbonFootprintAuthorizerTest extends TestCase
{
    public function testOwnerCanListEventCarbonFootprints(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
        $subject = new EventCarbonFootprint(
            id: EventCarbonFootprintId::generate(),
            accountId: new AccountId('account-id-test'),
        );

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    public function testOwnerCanRegisterEventCarbonFootprints(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
        $subject = new EventCarbonFootprint(
            id: EventCarbonFootprintId::generate(),
            eventId: EventId::generate(),
            accountId: new AccountId('account-id-test'),
        );

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Register, $user));
    }

    public function testOwnerCanEditEventCarbonFootprintFromSameAccount(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
        $subject = EventCarbonFootprintMother::fromEventIdAndAccount((string) EventId::generate(), 'account-id-test');

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Edit, $user));
    }

    public function testOwnerCannotEditEventCarbonFootprintFromOtherAccount(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
        $subject = EventCarbonFootprintMother::fromEventIdAndAccount((string) EventId::generate(), 'other-account-id');

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::Edit, $user));
    }

    public function testOwnerCanRemoveEventCarbonFootprintFromSameAccount(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
        $subject = EventCarbonFootprintMother::fromEventIdAndAccount((string) EventId::generate(), 'account-id-test');

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Remove, $user));
    }

    public function testOwnerCannotRemoveEventCarbonFootprintFromOtherAccount(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
        $subject = EventCarbonFootprintMother::fromEventIdAndAccount((string) EventId::generate(), 'other-account-id');

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::Remove, $user));
    }

    public function testUserWithoutAccountCannotList(): void
    {
        $user = UserMother::fromUsernameAndRole('owner', Role::Owner);
        $subject = new EventCarbonFootprint(
            id: EventCarbonFootprintId::generate(),
            accountId: new AccountId('account-id-test'),
        );

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    public function testAdminCannotList(): void
    {
        $user = UserMother::adminUser();
        $subject = new EventCarbonFootprint(
            id: EventCarbonFootprintId::generate(),
            accountId: new AccountId('account-id-test'),
        );

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    public function testOwnerCanExportFromSameAccount(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
        $subject = EventCarbonFootprintMother::fromEventIdAndAccount((string) EventId::generate(), 'account-id-test');

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Export, $user));
    }

    public function testOwnerCannotExportFromOtherAccount(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
        $subject = EventCarbonFootprintMother::fromEventIdAndAccount((string) EventId::generate(), 'other-account-id');

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::Export, $user));
    }

    public function testUserCannotExport(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('user', Role::User, new AccountId('account-id-test'));
        $subject = EventCarbonFootprintMother::fromEventIdAndAccount((string) EventId::generate(), 'account-id-test');

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::Export, $user));
    }

    public function testOwnerCannotListNorEditEventCarbonFootprintsWithoutEventsFeature(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
        $subject = EventCarbonFootprintMother::fromEventIdAndAccount((string) EventId::generate(), 'account-id-test');
        $sut = $this->createSUTWithFeatures(new AccountFeatures(events: false));

        foreach ([Action::List, Action::Register, Action::View, Action::Edit, Action::Remove, Action::Export] as $action) {
            $this->assertFalse($sut->canDoThat($subject, $action, $user));
        }
    }

    public function testOwnerCanListAndEditEventCarbonFootprintsWithEventsFeature(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
        $subject = EventCarbonFootprintMother::fromEventIdAndAccount((string) EventId::generate(), 'account-id-test');
        $sut = $this->createSUTWithFeatures(new AccountFeatures());

        $this->assertTrue($sut->canDoThat($subject, Action::List, $user));
        $this->assertTrue($sut->canDoThat($subject, Action::Edit, $user));
    }

    private function createSUT(): EventCarbonFootprintAuthorizer
    {
        return new EventCarbonFootprintAuthorizer();
    }

    private function createSUTWithFeatures(AccountFeatures $features): EventCarbonFootprintAuthorizer
    {
        return new EventCarbonFootprintAuthorizer(
            currentAccountIdProvider: new StubCurrentAccountIdProvider(new AccountId('account-id-test')),
            accountFeaturesPolicy: AccountFeaturesPolicyMother::forAccount(new AccountId('account-id-test'), $features),
        );
    }
}
