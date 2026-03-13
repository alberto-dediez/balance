<?php

namespace App\Tests\Unit\FoodLoss\Application\Action\Register;

use PHPUnit\Framework\TestCase;
use App\FoodLoss\Domain\Model\Action\Action;
use App\Shared\Domain\Model\Locale\Locale;
use App\FoodLoss\Application\Action\ActionAuthorizer;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\FoodLoss\Domain\Model\Action\ActionRegistered;
use App\FoodLoss\Domain\Model\Action\ActionTranslated;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\FoodLoss\Application\Action\Register\RegisterActionRequest;
use App\FoodLoss\Application\Action\Register\RegisterActionService;
use App\FoodLoss\Application\Action\Register\RegisterActionResponse;
use App\FoodLoss\Domain\Model\Action\ActionRepository;
use App\FoodLoss\Domain\Model\Action\Priority;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleGetter;
use App\Tests\Unit\FoodLoss\Domain\Model\Action\ActionRepositoryMother;

class RegisterActionServiceTest extends TestCase
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

        $sut = $this->createSUT($repository, Locale::Spanish);

        $request = new RegisterActionRequest(
            description: 'Action 1',
            priority: Priority::Prevention
        );

        $result = $sut($request, UserMother::adminUser());

        $this->assertNotNull($result);
        $this->assertTrue($result instanceof RegisterActionResponse);
        $this->assertNotEmpty((string) $result->actionId);
        $this->assertEquals(36, strlen((string) $result->actionId));
        $this->assertEquals('Action 1', $result->description);
        $this->assertEquals(Priority::Prevention, $result->priority);

        $actionPersisted = $repository->find($result->actionId);

        $this->assertNotNull($actionPersisted);
        $this->assertTrue($actionPersisted instanceof Action);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof ActionRegistered);
        $this->assertEquals('Action 1', $lastEvent->description());
        $this->assertEquals(Priority::Prevention, $lastEvent->priority());
        $this->assertTrue($actionPersisted->id()->equals($lastEvent->actionId()));
    }

    public function testRegisterWithLocaleTranslate(): void
    {
        $repository = ActionRepositoryMother::inMemory(Locale::Spanish);

        $sut = $this->createSUT($repository, Locale::English);

        $request = new RegisterActionRequest(
            description: 'changedDescription',
            priority: Priority::Prevention
        );

        $result = $sut($request, UserMother::adminUser());

        $this->assertNotNull($result);
        $this->assertTrue($result instanceof RegisterActionResponse);

        $actionPersisted = $repository->find($result->actionId);

        $this->assertNotNull($actionPersisted);
        $this->assertTrue($actionPersisted instanceof Action);
        $this->assertEquals('changedDescription', $actionPersisted->description());
        $this->assertEquals(Priority::Prevention, $actionPersisted->priority());

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof ActionTranslated);
        $this->assertEquals('changedDescription', $lastEvent->description());
        $this->assertEquals(Locale::English, $lastEvent->locale());
        $this->assertTrue($actionPersisted->id()->equals($lastEvent->actionId()));
    }

    public function testIsHandle(): void
    {
        $repository = ActionRepositoryMother::inMemory(Locale::Spanish);

        $sut = $this->createSUT($repository, Locale::Spanish);

        $result = $sut->isHandled(new RegisterActionRequest(
            description: 'changedDescription',
            priority: Priority::Prevention
        ));

        $this->assertTrue($result);
    }

    private function createSUT(ActionRepository $repository, Locale $locale): RegisterActionService
    {
        return new RegisterActionService(
            actionRepository: $repository, 
            localeGetter: new FakeLocaleGetter(
                locale: $locale
            ),
            authorizer: new ActionAuthorizer()
        );
    }
}