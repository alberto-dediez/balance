<?php

declare(strict_types=1);

namespace App\Tests\Unit\Waste\Application\Alert\View;

use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Waste\Domain\Model\Alert\AlertMother;
use App\Tests\Unit\Waste\Domain\Model\Alert\AlertRepositoryMother;
use App\Waste\Application\Alert\AlertAuthorizer;
use App\Waste\Application\Alert\View\ViewAlertRequest;
use App\Waste\Application\Alert\View\ViewAlertService;
use App\Waste\Domain\Model\Alert\AlertAcknowledged;
use App\Waste\Domain\Model\Alert\AlertId;
use App\Waste\Domain\Model\Alert\AlertRepository;
use App\Waste\Domain\Model\Alert\AlertStatus;
use PHPUnit\Framework\TestCase;

class ViewAlertServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testViewAlertAutoAcknowledgesWhenNew(): void
    {
        $repository = AlertRepositoryMother::inMemory();
        $alert = AlertMother::fromIdAndAccount((string) AlertId::generate(), 'account-id-test');
        $repository->add($alert);

        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));

        $result = $this->createSUT($repository)(new ViewAlertRequest($alert->id()), $user);

        $persisted = $repository->find($alert->id());
        $this->assertNotNull($persisted);
        $this->assertEquals(AlertStatus::Acknowledged, $persisted->status());

        $this->assertEquals($alert->id(), $result->alert->id());

        $lastEvent = $this->spySubscriber->lastEvent();
        $this->assertNotNull($lastEvent);
        $this->assertInstanceOf(AlertAcknowledged::class, $lastEvent);
    }

    public function testViewAlertDoesNotAcknowledgeWhenAlreadyAcknowledged(): void
    {
        $repository = AlertRepositoryMother::inMemory();
        $alert = AlertMother::fromIdAndAccount((string) AlertId::generate(), 'account-id-test');
        $alert->acknowledge();
        $repository->add($alert);

        DomainEventPublisher::instance()->reset();
        $this->spySubscriber = new SpyDomainEventSubscriber();

        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));

        $result = $this->createSUT($repository)(new ViewAlertRequest($alert->id()), $user);

        $this->assertEquals(AlertStatus::Acknowledged, $result->alert->status());

        $this->assertNull($this->spySubscriber->lastEvent());
    }

    public function testViewAlertThrowsWhenNotFound(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));

        $this->createSUT(AlertRepositoryMother::inMemory())(
            new ViewAlertRequest(AlertId::generate()),
            $user,
        );
    }

    public function testViewAlertThrowsWhenUnauthorized(): void
    {
        $this->expectException(UnauthorizedActionException::class);

        $repository = AlertRepositoryMother::inMemory();
        $alert = AlertMother::fromIdAndAccount((string) AlertId::generate(), 'account-id-test');
        $repository->add($alert);

        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('other-account-id'));

        $this->createSUT($repository)(new ViewAlertRequest($alert->id()), $user);
    }

    private function createSUT(AlertRepository $repository): ViewAlertService
    {
        return new ViewAlertService(
            repository: $repository,
            authorizer: new AlertAuthorizer(),
        );
    }
}
