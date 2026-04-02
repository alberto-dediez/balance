<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Application\Reason\Remove;

use App\FoodLoss\Application\Reason\ReasonAuthorizer;
use App\FoodLoss\Application\Reason\Remove\RemoveReasonRequest;
use App\FoodLoss\Application\Reason\Remove\RemoveReasonService;
use App\FoodLoss\Domain\Model\Reason\ReasonRemoved;
use App\FoodLoss\Domain\Model\Reason\ReasonRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\FoodLoss\Domain\Model\Reason\ReasonMother;
use App\Tests\Unit\FoodLoss\Domain\Model\Reason\ReasonRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use PHPUnit\Framework\TestCase;

class RemoveReasonServiceTest extends TestCase
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

        $reason = ReasonMother::registeredReason();
        $repository->add($reason);

        $sut = $this->createSUT($repository);

        $sut(new RemoveReasonRequest($reason->id()), UserMother::adminUser());

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof ReasonRemoved);
        $this->assertTrue($reason->id()->equals($lastEvent->reasonId()));
    }

    public function testIsHandle(): void
    {
        $repository = ReasonRepositoryMother::inMemory();
        $reason = ReasonMother::registeredReason();
        $repository->add($reason);

        $sut = $this->createSUT($repository);

        $result = $sut->isHandled(new RemoveReasonRequest($reason->id()));

        $this->assertTrue($result);
    }

    private function createSUT(ReasonRepository $repository): RemoveReasonService
    {
        return new RemoveReasonService(
            repository: $repository,
            authorizer: new ReasonAuthorizer(),
        );
    }
}
