<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Application\Action\Update;

use App\FoodLoss\Application\Action\ActionAuthorizer;
use App\FoodLoss\Application\Action\Update\UpdateActionRequest;
use App\FoodLoss\Application\Action\Update\UpdateActionService;
use App\FoodLoss\Domain\Model\Action\ActionRepository;
use App\FoodLoss\Domain\Model\Action\ActionTranslated;
use App\FoodLoss\Domain\Model\Action\ActionUpdated;
use App\FoodLoss\Domain\Model\Action\Priority;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\FoodLoss\Domain\Model\Action\ActionMother;
use App\Tests\Unit\FoodLoss\Domain\Model\Action\ActionRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use PHPUnit\Framework\TestCase;

class UpdateActionServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        FakeLocaleSetter::reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testUseCase(): void
    {
        $repository = ActionRepositoryMother::inMemory();

        $action = ActionMother::random();

        $repository->add($action);

        $user = UserMother::fromUsernameAndRole('admin', Role::Admin);

        $sut = $this->createSUT($repository, Locale::Spanish);

        $request = new UpdateActionRequest(
            actionId: $action->id(),
            description: 'changedDescription',
            priority: Priority::Transformation,
        );

        $result = $sut($request, $user);

        $actionPersisted = $repository->find($result->actionId);

        $this->assertNotNull($actionPersisted);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof ActionUpdated);
        $this->assertEquals(Priority::Transformation, $lastEvent->priority());
        $this->assertTrue($actionPersisted->id()->equals($lastEvent->actionId()));
    }

    public function testUpdateWithLocaleTranslate(): void
    {
        $repository = ActionRepositoryMother::inMemory(Locale::Spanish);

        $action = ActionMother::random();

        $repository->add($action);

        $user = UserMother::fromUsernameAndRole('admin', Role::Admin);

        $sut = $this->createSUT($repository, Locale::English);

        $request = new UpdateActionRequest(
            actionId: $action->id(),
            description: 'changedDescription',
            priority: Priority::Prevention,
        );

        $result = $sut($request, $user);

        $actionPersisted = $repository->find($result->actionId);

        FakeLocaleSetter::set(Locale::Spanish);

        $this->assertNotNull($actionPersisted);
        $this->assertNotEquals('changedDescription', $actionPersisted->description());

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof ActionTranslated);
        $this->assertEquals('changedDescription', $lastEvent->description());
        $this->assertEquals(Locale::English, $lastEvent->locale());
        $this->assertTrue($actionPersisted->id()->equals($lastEvent->actionId()));
    }

    public function testIsHandle(): void
    {
        $repository = ActionRepositoryMother::inMemory();

        $sut = $this->createSUT($repository, Locale::English);

        $action = ActionMother::random();

        $result = $sut->isHandled(new UpdateActionRequest(
            actionId: $action->id(),
            description: 'changedDescription',
            priority: Priority::Prevention,
        ));

        $this->assertTrue($result);
    }

    private function createSUT(ActionRepository $repository, Locale $locale): UpdateActionService
    {
        FakeLocaleSetter::set($locale);

        return new UpdateActionService(
            actionRepository: $repository,
            authorizer: new ActionAuthorizer(),
        );
    }
}
