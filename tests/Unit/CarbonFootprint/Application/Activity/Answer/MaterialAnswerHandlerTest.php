<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Activity\Answer;

use App\CarbonFootprint\Application\Activity\ActivityAuthorizer;
use App\CarbonFootprint\Application\Activity\Answer\MaterialAnswerHandler;
use App\CarbonFootprint\Application\Activity\MaterialEmissionFactorSelector;
use App\CarbonFootprint\Application\Activity\Register\RegisterMaterialActivityService;
use App\CarbonFootprint\Application\Activity\Update\UpdateMaterialActivityService;
use App\CarbonFootprint\Domain\Model\Activity\Answer\MaterialAnswer;
use App\CarbonFootprint\Domain\Model\Activity\ItemCondition;
use App\CarbonFootprint\Domain\Model\Activity\MaterialActivity;
use App\CarbonFootprint\Domain\Model\Material\Material;
use App\CarbonFootprint\Domain\Model\Quantity;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Application\Activity\EmissionsCalculatorMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Material\MaterialMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Material\MaterialRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class MaterialAnswerHandlerTest extends TestCase
{
    private Material $material;
    private MaterialAnswerHandler $sut;
    private \App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryActivityRepository $activityRepository;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->activityRepository = ActivityRepositoryMother::inMemory();

        $this->material = MaterialMother::registeredMaterial();
        $this->material->setEmissionFactor(ActivityMother::emissionFactor()->withYear(2026), ActivityMother::emissionFactor()->withYear(2026));
        $materialRepository = MaterialRepositoryMother::inMemory();
        $materialRepository->add($this->material);

        $authorizer = new ActivityAuthorizer();
        $calculator = EmissionsCalculatorMother::create();
        $selector = new MaterialEmissionFactorSelector($materialRepository);

        $this->sut = new MaterialAnswerHandler(
            new RegisterMaterialActivityService($this->activityRepository, $authorizer, $selector, $calculator),
            new UpdateMaterialActivityService($this->activityRepository, $authorizer, $selector, $calculator),
        );
    }

    public function testRegistersAnActivityFromTheAnswer(): void
    {
        $answer = new MaterialAnswer(
            materialId: $this->material->id(),
            condition: ItemCondition::New,
            quantity: new Quantity(10.0, Unit::Kilogram),
        );

        $activityId = $this->sut->register($answer, 2026, EmissionFactorGroupMother::defaultId(), new AccountId('account-id'), $this->ownerUser());

        $activity = $this->activityRepository->findById($activityId);
        $this->assertInstanceOf(MaterialActivity::class, $activity);
        $this->assertTrue($this->material->id()->equals($activity->materialId()));
    }

    public function testUpdatesAnExistingActivityFromTheAnswer(): void
    {
        $registerAnswer = new MaterialAnswer(
            materialId: $this->material->id(),
            condition: ItemCondition::New,
            quantity: new Quantity(10.0, Unit::Kilogram),
        );
        $activityId = $this->sut->register($registerAnswer, 2026, EmissionFactorGroupMother::defaultId(), new AccountId('account-id'), $this->ownerUser());

        $updateAnswer = new MaterialAnswer(
            materialId: $this->material->id(),
            condition: ItemCondition::New,
            quantity: new Quantity(20.0, Unit::Kilogram),
            existingActivityId: $activityId,
        );
        $this->sut->update($updateAnswer, 2026, EmissionFactorGroupMother::defaultId(), $this->ownerUser());

        $activity = $this->activityRepository->findById($activityId);
        $this->assertNotNull($activity);
        $this->assertSame(20.0, $activity->quantity()->value());
    }

    private function ownerUser(): \App\Security\Domain\Model\User\User
    {
        return UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id'));
    }
}
