<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Application\Protocol\Register;

use App\FoodLoss\Application\Protocol\ProtocolAuthorizer;
use App\FoodLoss\Application\Protocol\ProtocolUploader;
use App\FoodLoss\Application\Protocol\Register\RegisterProtocolRequest;
use App\FoodLoss\Application\Protocol\Register\RegisterProtocolService;
use App\FoodLoss\Domain\Model\Protocol\ProtocolRegistered;
use App\FoodLoss\Domain\Model\Protocol\ProtocolRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\FoodLoss\Domain\Model\Protocol\ProtocolRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RegisterProtocolServiceTest extends TestCase
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
        $sut = $this->createSUT($repository);

        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-test'));

        $request = new RegisterProtocolRequest(
            name: 'Protocol 1',
            description: 'A description',
            accountId: new AccountId('account-test'),
            temporaryFilePath: '/tmp/test.pdf',
            fileExtension: 'pdf',
        );

        $result = $sut($request, $user);

        $this->assertNotEmpty((string) $result->protocolId);
        $this->assertEquals(36, strlen((string) $result->protocolId));
        $this->assertEquals('Protocol 1', $result->name);

        $persisted = $repository->find($result->protocolId);
        $this->assertNotNull($persisted);

        $lastEvent = $this->spySubscriber->lastEvent();
        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof ProtocolRegistered);
        $this->assertEquals('Protocol 1', $lastEvent->name());
        $this->assertTrue($persisted->id()->equals($lastEvent->protocolId()));
    }

    public function testIsHandle(): void
    {
        $repository = ProtocolRepositoryMother::inMemory();
        $sut = $this->createSUT($repository);

        $result = $sut->isHandled(new RegisterProtocolRequest(
            name: 'test',
            description: 'test',
        ));

        $this->assertTrue($result);
    }

    private function createSUT(ProtocolRepository $repository): RegisterProtocolService
    {
        $fileUploader = new class implements ProtocolUploader {
            public function upload(string $sourcePath, string $extension, AccountId $accountId): string
            {
                return 'protocols/' . $accountId . '/test.' . $extension;
            }
        };

        return new RegisterProtocolService(
            protocolRepository: $repository,
            authorizer: new ProtocolAuthorizer(),
            fileUploader: $fileUploader,
        );
    }
}
