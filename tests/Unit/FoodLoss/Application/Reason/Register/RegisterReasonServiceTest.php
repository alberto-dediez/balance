<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Application\Reason\Register;

use App\FoodLoss\Application\Reason\ReasonAuthorizer;
use App\FoodLoss\Application\Reason\Register\RegisterReasonRequest;
use App\FoodLoss\Application\Reason\Register\RegisterReasonService;
use App\FoodLoss\Domain\Model\Action\Priority;
use App\FoodLoss\Domain\Model\Reason\ReasonRegistered;
use App\FoodLoss\Domain\Model\Reason\ReasonRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\FoodLoss\Domain\Model\Reason\ReasonRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use PHPUnit\Framework\TestCase;

class RegisterReasonServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        FakeLocaleSetter::reset();
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testUseCase(): void
    {
        $repository = ReasonRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $request = new RegisterReasonRequest(
            description: 'Reason 1',
            priority: Priority::Prevention,
        );

        $result = $sut($request, UserMother::adminUser());

        $this->assertNotEmpty((string) $result->reasonId);
        $this->assertEquals(36, strlen((string) $result->reasonId));
        $this->assertEquals('Reason 1', $result->description);

        $reasonPersisted = $repository->find($result->reasonId);

        $this->assertNotNull($reasonPersisted);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof ReasonRegistered);
        $this->assertEquals('Reason 1', $lastEvent->description());
        $this->assertTrue($reasonPersisted->id()->equals($lastEvent->reasonId()));
    }

    public function testIsHandle(): void
    {
        $repository = ReasonRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $result = $sut->isHandled(new RegisterReasonRequest(
            description: 'test',
        ));

        $this->assertTrue($result);
    }

    private function createSUT(ReasonRepository $repository, Locale $locale = Locale::Spanish): RegisterReasonService
    {
        FakeLocaleSetter::set($locale);

        return new RegisterReasonService(
            reasonRepository: $repository,
            authorizer: new ReasonAuthorizer(),
        );
    }
}
