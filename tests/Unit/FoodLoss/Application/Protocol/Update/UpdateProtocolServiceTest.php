<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Application\Protocol\Update;

use App\FoodLoss\Application\Protocol\ProtocolAuthorizer;
use App\FoodLoss\Application\Protocol\ProtocolUploader;
use App\FoodLoss\Application\Protocol\Update\UpdateProtocolRequest;
use App\FoodLoss\Application\Protocol\Update\UpdateProtocolService;
use App\FoodLoss\Domain\Model\Protocol\ProtocolRepository;
use App\FoodLoss\Domain\Model\Protocol\ProtocolUpdated;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\FoodLoss\Domain\Model\Protocol\ProtocolMother;
use App\Tests\Unit\FoodLoss\Domain\Model\Protocol\ProtocolRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class UpdateProtocolServiceTest extends TestCase
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
        $protocol = ProtocolMother::from(name: 'Old Name', id: 'protocol-id-1', accountId: 'account-test');
        $repository->add($protocol);

        $sut = $this->createSUT($repository);

        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-test'));

        $request = new UpdateProtocolRequest(
            protocolId: $protocol->id(),
            name: 'New Name',
            description: 'Updated description',
        );

        $result = $sut($request, $user);

        $this->assertEquals('New Name', $result->name);

        $persisted = $repository->find($result->protocolId);
        $this->assertNotNull($persisted);
        $this->assertEquals('New Name', $persisted->name());

        $lastEvent = $this->spySubscriber->lastEvent();
        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof ProtocolUpdated);
        $this->assertEquals('New Name', $lastEvent->name());
    }

    public function testIsHandle(): void
    {
        $repository = ProtocolRepositoryMother::inMemory();
        $protocol = ProtocolMother::registeredProtocol();
        $repository->add($protocol);

        $sut = $this->createSUT($repository);

        $result = $sut->isHandled(new UpdateProtocolRequest(
            protocolId: $protocol->id(),
            name: 'test',
            description: 'test',
        ));

        $this->assertTrue($result);
    }

    private function createSUT(ProtocolRepository $repository): UpdateProtocolService
    {
        $fileUploader = new class implements ProtocolUploader {
            public function upload(string $sourcePath, string $extension, AccountId $accountId): string
            {
                return 'protocols/' . $accountId . '/test.' . $extension;
            }
        };

        return new UpdateProtocolService(
            protocolRepository: $repository,
            authorizer: new ProtocolAuthorizer(),
            fileUploader: $fileUploader,
        );
    }
}
