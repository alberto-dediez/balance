<?php

declare(strict_types=1);

namespace App\Tests\Unit\Waste\Application\Alert\Acknowledge;

use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Waste\Domain\Model\Alert\AlertMother;
use App\Tests\Unit\Waste\Domain\Model\Alert\AlertRepositoryMother;
use App\Waste\Application\Alert\Acknowledge\AcknowledgeAlertRequest;
use App\Waste\Application\Alert\Acknowledge\AcknowledgeAlertService;
use App\Waste\Application\Alert\AlertAuthorizer;
use App\Waste\Domain\Model\Alert\AlertAcknowledged;
use App\Waste\Domain\Model\Alert\AlertId;
use App\Waste\Domain\Model\Alert\AlertRepository;
use App\Waste\Domain\Model\Alert\AlertStatus;
use PHPUnit\Framework\TestCase;

class AcknowledgeAlertServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testAcknowledgeAlert(): void
    {
        $repository = AlertRepositoryMother::inMemory();
        $alert = AlertMother::fromIdAndAccount((string) AlertId::generate(), 'account-id-test');
        $repository->add($alert);

        $sut = $this->createSUT($repository);

        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));

        $result = $sut(new AcknowledgeAlertRequest($alert->id()), $user);

        $this->assertInstanceOf(\App\Waste\Application\Alert\Acknowledge\AcknowledgeAlertResponse::class, $result);

        $recovered = $repository->find($alert->id());
        $this->assertNotNull($recovered);
        $this->assertEquals(AlertStatus::Acknowledged, $recovered->status());

        $lastEvent = $this->spySubscriber->lastEvent();
        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof AlertAcknowledged);
    }

    public function testIsHandled(): void
    {
        $repository = AlertRepositoryMother::inMemory();
        $sut = $this->createSUT($repository);

        $this->assertTrue($sut->isHandled(new AcknowledgeAlertRequest(AlertId::generate())));
    }

    private function createSUT(AlertRepository $repository): AcknowledgeAlertService
    {
        return new AcknowledgeAlertService(
            alertRepository: $repository,
            authorizer: new AlertAuthorizer(),
        );
    }
}
