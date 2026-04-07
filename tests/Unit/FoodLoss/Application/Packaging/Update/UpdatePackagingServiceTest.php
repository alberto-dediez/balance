<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Application\Packaging\Update;

use App\FoodLoss\Application\Packaging\PackagingAuthorizer;
use App\FoodLoss\Application\Packaging\Update\UpdatePackagingRequest;
use App\FoodLoss\Application\Packaging\Update\UpdatePackagingService;
use App\FoodLoss\Domain\Model\Packaging\PackagingRepository;
use App\FoodLoss\Domain\Model\Packaging\PackagingType;
use App\FoodLoss\Domain\Model\Packaging\PackagingUpdated;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\FoodLoss\Domain\Model\Packaging\PackagingMother;
use App\Tests\Unit\FoodLoss\Domain\Model\Packaging\PackagingRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class UpdatePackagingServiceTest extends TestCase
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

        $packaging = PackagingMother::from(name: 'Original', id: 'packaging-test-id', accountId: 'account-id-test');
        $repository->add($packaging);

        $sut = $this->createSUT($repository);

        $request = new UpdatePackagingRequest(
            packagingId: $packaging->id(),
            name: 'Updated Name',
            packagingType: PackagingType::Compostable,
        );

        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));

        $result = $sut($request, $user);

        $this->assertEquals('Updated Name', $result->name);
        $this->assertEquals(PackagingType::Compostable, $result->packagingType);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof PackagingUpdated);
        $this->assertEquals('Updated Name', $lastEvent->name());
    }

    public function testIsHandle(): void
    {
        $repository = PackagingRepositoryMother::inMemory();
        $packaging = PackagingMother::registeredPackaging();
        $repository->add($packaging);

        $sut = $this->createSUT($repository);

        $result = $sut->isHandled(new UpdatePackagingRequest(
            packagingId: $packaging->id(),
            name: 'test',
            packagingType: PackagingType::Reusable,
        ));

        $this->assertTrue($result);
    }

    private function createSUT(PackagingRepository $repository): UpdatePackagingService
    {
        return new UpdatePackagingService(
            packagingRepository: $repository,
            authorizer: new PackagingAuthorizer(),
        );
    }
}
