<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Application\Action\Remove;

use App\FoodLoss\Application\Action\ActionAuthorizer;
use App\FoodLoss\Application\Action\Remove\RemoveActionRequest;
use App\FoodLoss\Application\Action\Remove\RemoveActionService;
use App\FoodLoss\Domain\Model\Action\ActionRemoved;
use App\FoodLoss\Domain\Model\Action\ActionRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\FoodLoss\Domain\Model\Action\ActionMother;
use App\Tests\Unit\FoodLoss\Domain\Model\Action\ActionRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RemoveActionServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testInvoke(): void
    {
        $repository = ActionRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $action = ActionMother::registeredAction();

        $repository->add($action);

        $request = new RemoveActionRequest(
            actionId: $action->id(),
        );

        $result = $sut($request, UserMother::adminUser());

        $this->assertTrue($action->id()->equals($result->actionId));

        $actionPersisted = $repository->find($result->actionId);

        $this->assertNull($actionPersisted);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof ActionRemoved);
    }

    public function testIsHandle(): void
    {
        $repository = ActionRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $action = ActionMother::random();

        $result = $sut->isHandled(new RemoveActionRequest(
            actionId: $action->id(),
        ));

        $this->assertTrue($result);
    }

    private function createSUT(ActionRepository $repository): RemoveActionService
    {
        return new RemoveActionService($repository, new ActionAuthorizer());
    }
}
