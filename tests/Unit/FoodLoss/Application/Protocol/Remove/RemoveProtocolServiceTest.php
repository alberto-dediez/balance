<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Application\Protocol\Remove;

use App\FoodLoss\Application\Protocol\ProtocolAuthorizer;
use App\FoodLoss\Application\Protocol\Remove\RemoveProtocolRequest;
use App\FoodLoss\Application\Protocol\Remove\RemoveProtocolService;
use App\FoodLoss\Domain\Model\Protocol\ProtocolRemoved;
use App\FoodLoss\Domain\Model\Protocol\ProtocolRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\FoodLoss\Domain\Model\Protocol\ProtocolMother;
use App\Tests\Unit\FoodLoss\Domain\Model\Protocol\ProtocolRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RemoveProtocolServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testUseCase(): void
    {
        $repository = ProtocolRepositoryMother::inMemory();
        $protocol = ProtocolMother::from(name: 'Test Protocol', id: 'protocol-id-1', accountId: 'account-test');
        $repository->add($protocol);

        $sut = $this->createSUT($repository);

        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-test'));

        $result = $sut(new RemoveProtocolRequest($protocol->id()), $user);

        $this->assertTrue($protocol->id()->equals($result->protocolId));

        $this->assertNull($repository->find($protocol->id()));

        $lastEvent = $this->spySubscriber->lastEvent();
        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof ProtocolRemoved);
        $this->assertTrue($protocol->id()->equals($lastEvent->protocolId()));
    }

    public function testIsHandle(): void
    {
        $repository = ProtocolRepositoryMother::inMemory();
        $protocol = ProtocolMother::registeredProtocol();
        $repository->add($protocol);

        $sut = $this->createSUT($repository);

        $result = $sut->isHandled(new RemoveProtocolRequest($protocol->id()));

        $this->assertTrue($result);
    }

    private function createSUT(ProtocolRepository $repository): RemoveProtocolService
    {
        return new RemoveProtocolService(
            repository: $repository,
            authorizer: new ProtocolAuthorizer(),
        );
    }
}
