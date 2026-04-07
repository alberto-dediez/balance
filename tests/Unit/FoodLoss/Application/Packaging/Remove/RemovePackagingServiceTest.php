<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Application\Packaging\Remove;

use App\FoodLoss\Application\Packaging\PackagingAuthorizer;
use App\FoodLoss\Application\Packaging\Remove\RemovePackagingRequest;
use App\FoodLoss\Application\Packaging\Remove\RemovePackagingService;
use App\FoodLoss\Domain\Model\Packaging\PackagingRemoved;
use App\FoodLoss\Domain\Model\Packaging\PackagingRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\FoodLoss\Domain\Model\Packaging\PackagingMother;
use App\Tests\Unit\FoodLoss\Domain\Model\Packaging\PackagingRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RemovePackagingServiceTest extends TestCase
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

        $packaging = PackagingMother::from(name: 'Test', id: 'packaging-test-id', accountId: 'account-id-test');
        $repository->add($packaging);

        $sut = $this->createSUT($repository);

        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));

        $result = $sut(new RemovePackagingRequest($packaging->id()), $user);

        $this->assertTrue($packaging->id()->equals($result->packagingId));

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof PackagingRemoved);
        $this->assertTrue($packaging->id()->equals($lastEvent->packagingId()));

        $this->assertNull($repository->find($packaging->id()));
    }

    public function testIsHandle(): void
    {
        $repository = PackagingRepositoryMother::inMemory();
        $packaging = PackagingMother::registeredPackaging();
        $repository->add($packaging);

        $sut = $this->createSUT($repository);

        $result = $sut->isHandled(new RemovePackagingRequest($packaging->id()));

        $this->assertTrue($result);
    }

    private function createSUT(PackagingRepository $repository): RemovePackagingService
    {
        return new RemovePackagingService(
            repository: $repository,
            authorizer: new PackagingAuthorizer(),
        );
    }
}
