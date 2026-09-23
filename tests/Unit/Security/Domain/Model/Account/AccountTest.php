<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Domain\Model\Account;

use App\Security\Domain\Model\Account\AccountConsultantChanged;
use App\Security\Domain\Model\Account\AccountConsultantStatusChanged;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\Account\AccountReportPermissions;
use App\Security\Domain\Model\Account\AccountReportPermissionsChanged;
use App\Security\Domain\Model\Account\ConsultantCannotHaveConsultantException;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class AccountTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testChangeConsultantPublishesEvent(): void
    {
        $account = AccountMother::fromName('MyAccount');
        $consultantAccountId = AccountId::generate();

        $account->changeConsultant($consultantAccountId);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertTrue($lastEvent instanceof AccountConsultantChanged);
        $this->assertNotNull($account->consultantAccountId());
        $this->assertTrue($consultantAccountId->equals($account->consultantAccountId()));
    }

    public function testChangeConsultantToNullReleasesTheConsultant(): void
    {
        $account = AccountMother::fromNameWithConsultant('MyAccount', AccountId::generate());

        $account->changeConsultant(null);

        $this->assertNull($account->consultantAccountId());
    }

    public function testChangeConsultantStatusPublishesEvent(): void
    {
        $account = AccountMother::fromName('MyAccount');

        $account->changeConsultantStatus(true);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertTrue($lastEvent instanceof AccountConsultantStatusChanged);
        $this->assertTrue($account->isConsultant());
    }

    public function testAccountWithConsultantCannotBeMarkedAsConsultant(): void
    {
        $this->expectException(ConsultantCannotHaveConsultantException::class);

        $account = AccountMother::fromNameWithConsultant('MyAccount', AccountId::generate());

        $account->changeConsultantStatus(true);
    }

    public function testConsultantCannotBeAssignedAConsultant(): void
    {
        $this->expectException(ConsultantCannotHaveConsultantException::class);

        $account = AccountMother::consultantAccount();

        $account->changeConsultant(AccountId::generate());
    }

    public function testReportPermissionsDefaultToNoEditionAndAutoReview(): void
    {
        $account = AccountMother::fromName('MyAccount');

        $this->assertFalse($account->reportPermissions()->canEditTexts());
        $this->assertTrue($account->reportPermissions()->autoReview());
    }

    public function testChangeReportPermissionsPublishesEventAndUpdatesThem(): void
    {
        $account = AccountMother::fromName('MyAccount');

        $account->changeReportPermissions(new AccountReportPermissions(true, false));

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertInstanceOf(AccountReportPermissionsChanged::class, $lastEvent);
        $this->assertTrue($lastEvent->canEditTexts());
        $this->assertFalse($lastEvent->autoReview());
        $this->assertTrue($account->reportPermissions()->canEditTexts());
        $this->assertFalse($account->reportPermissions()->autoReview());
    }

    public function testChangeReportPermissionsWithoutChangesDoesNotPublish(): void
    {
        $account = AccountMother::fromName('MyAccount');
        $eventsBefore = count($this->spySubscriber->events());

        $account->changeReportPermissions(new AccountReportPermissions(false, true));

        $this->assertCount($eventsBefore, $this->spySubscriber->events());
    }
}
