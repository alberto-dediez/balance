<?php

namespace App\Tests\Unit\FoodLoss\Application\Action\Update;

use PHPUnit\Framework\TestCase;
use App\FoodLoss\Domain\Model\Action\Action;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Model\Locale\Locale;
use App\FoodLoss\Domain\Model\Action\ActionUpdated;
use App\FoodLoss\Application\Action\ActionAuthorizer;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\FoodLoss\Domain\Model\Action\ActionTranslated;
use App\Tests\Unit\FoodLoss\Domain\Model\Action\ActionMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\FoodLoss\Application\Action\Update\UpdateActionRequest;
use App\FoodLoss\Application\Action\Update\UpdateActionService;
use App\FoodLoss\Application\Action\Update\UpdateActionResponse;
use App\FoodLoss\Domain\Model\Action\Priority;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleGetter;
use App\Tests\Unit\FoodLoss\Domain\Model\Action\ActionRepositoryMother;

class UpdateActionServiceTest extends TestCase
{
    private $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

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
            priority: Priority::Transformation
        );

        $result = $sut($request, $user);

        $this->assertNotNull($result);
        $this->assertTrue($result instanceof UpdateActionResponse);

        $actionPersisted = $repository->find($result->actionId);

        $this->assertNotNull($actionPersisted);
        $this->assertTrue($actionPersisted instanceof Action);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof ActionUpdated);
        $this->assertEquals('changedDescription', $lastEvent->description());
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
            priority: Priority::Prevention
        );

        $result = $sut($request, $user);

        $this->assertNotNull($result);
        $this->assertTrue($result instanceof UpdateActionResponse);

        $actionPersisted = $repository->find($result->actionId);

        $this->assertNotNull($actionPersisted);
        $this->assertTrue($actionPersisted instanceof Action);
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
            priority: Priority::Prevention
        ));

        $this->assertTrue($result);
    }

    private function createSUT($repository, Locale $locale): UpdateActionService
    {
        return new UpdateActionService(
            actionRepository: $repository,
            localeGetter: new FakeLocaleGetter(
                locale: $locale
            ),
            authorizer: new ActionAuthorizer()
        );
    }
}