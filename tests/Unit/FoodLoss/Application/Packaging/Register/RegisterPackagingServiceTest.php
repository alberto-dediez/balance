<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Application\Packaging\Register;

use App\FoodLoss\Application\Packaging\PackagingAuthorizer;
use App\FoodLoss\Application\Packaging\Register\RegisterPackagingRequest;
use App\FoodLoss\Application\Packaging\Register\RegisterPackagingService;
use App\FoodLoss\Domain\Model\Packaging\PackagingRegistered;
use App\FoodLoss\Domain\Model\Packaging\PackagingRepository;
use App\FoodLoss\Domain\Model\Packaging\PackagingType;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\FoodLoss\Domain\Model\Packaging\PackagingRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RegisterPackagingServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testUseCase(): void
    {
        $repository = PackagingRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $request = new RegisterPackagingRequest(
            name: 'Envase 1',
            packagingType: PackagingType::Reusable,
            accountId: new AccountId('account-id-test'),
        );

        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));

        $result = $sut($request, $user);

        $this->assertNotEmpty((string) $result->packagingId);
        $this->assertEquals(36, strlen((string) $result->packagingId));
        $this->assertEquals('Envase 1', $result->name);
        $this->assertEquals(PackagingType::Reusable, $result->packagingType);

        $packagingPersisted = $repository->find($result->packagingId);

        $this->assertNotNull($packagingPersisted);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof PackagingRegistered);
        $this->assertEquals('Envase 1', $lastEvent->name());
        $this->assertTrue($packagingPersisted->id()->equals($lastEvent->packagingId()));
    }

    public function testIsHandle(): void
    {
        $repository = PackagingRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $result = $sut->isHandled(new RegisterPackagingRequest(
            name: 'test',
        ));

        $this->assertTrue($result);
    }

    private function createSUT(PackagingRepository $repository): RegisterPackagingService
    {
        return new RegisterPackagingService(
            packagingRepository: $repository,
            authorizer: new PackagingAuthorizer(),
        );
    }
}
