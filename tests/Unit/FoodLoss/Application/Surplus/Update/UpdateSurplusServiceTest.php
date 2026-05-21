<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Application\Surplus\Update;

use App\FoodLoss\Application\Surplus\SurplusAuthorizer;
use App\FoodLoss\Application\Surplus\Update\UpdateSurplusRequest;
use App\FoodLoss\Application\Surplus\Update\UpdateSurplusService;
use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointId;
use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogUnit;
use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryId;
use App\FoodLoss\Domain\Model\Surplus\FoodType;
use App\FoodLoss\Domain\Model\Surplus\SurplusRepository;
use App\FoodLoss\Domain\Model\Surplus\SurplusUpdated;
use App\FoodLoss\Domain\Model\SurplusCause\SurplusCauseId;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\FoodLoss\Domain\Model\Surplus\SurplusMother;
use App\Tests\Unit\FoodLoss\Domain\Model\Surplus\SurplusRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class UpdateSurplusServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testUseCase(): void
    {
        $accountId = new AccountId('account-id-test');
        $repository = SurplusRepositoryMother::inMemory();
        $surplus = SurplusMother::withAccountId($accountId);
        $repository->add($surplus);

        $sut = $this->createSUT($repository);

        $request = new UpdateSurplusRequest(
            surplusId: $surplus->id(),
            date: new \DateTimeImmutable('2026-05-22'),
            serviceCategoryId: new ServiceCategoryId('00000000-0000-0000-0000-000000000001'),
            criticalPointId: new CriticalPointId('00000000-0000-0000-0000-000000000001'),
            surplusCauseId: new SurplusCauseId('00000000-0000-0000-0000-000000000001'),
            foodType: FoodType::Fish,
            quantity: 10.0,
            unit: DeliveryLogUnit::Rations,
        );

        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId);
        $sut($request, $user);

        $updatedSurplus = $repository->find($surplus->id());

        $this->assertNotNull($updatedSurplus);
        $this->assertEquals(FoodType::Fish, $updatedSurplus->foodType());
        $this->assertEquals(10.0, $updatedSurplus->quantity());

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof SurplusUpdated);
    }

    private function createSUT(SurplusRepository $repository): UpdateSurplusService
    {
        return new UpdateSurplusService(
            surplusRepository: $repository,
            authorizer: new SurplusAuthorizer(),
        );
    }
}
