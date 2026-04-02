<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Application\Reason\Update;

use App\FoodLoss\Application\Reason\ReasonAuthorizer;
use App\FoodLoss\Application\Reason\Update\UpdateReasonRequest;
use App\FoodLoss\Application\Reason\Update\UpdateReasonService;
use App\FoodLoss\Domain\Model\Action\Priority;
use App\FoodLoss\Domain\Model\Reason\ReasonRepository;
use App\FoodLoss\Domain\Model\Reason\ReasonUpdated;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\FoodLoss\Domain\Model\Reason\ReasonMother;
use App\Tests\Unit\FoodLoss\Domain\Model\Reason\ReasonRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use PHPUnit\Framework\TestCase;

class UpdateReasonServiceTest extends TestCase
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

        $request = new UpdateReasonRequest(
            reasonId: $reason->id(),
            description: 'Updated description',
            priority: Priority::Transformation,
        );

        $result = $sut($request, UserMother::adminUser());

        $this->assertEquals('Updated description', $result->description);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof ReasonUpdated);
        $this->assertEquals('Updated description', $lastEvent->description());
    }

    public function testIsHandle(): void
    {
        $repository = ReasonRepositoryMother::inMemory();
        $reason = ReasonMother::registeredReason();
        $repository->add($reason);

        $sut = $this->createSUT($repository);

        $result = $sut->isHandled(new UpdateReasonRequest(
            reasonId: $reason->id(),
            description: 'test',
            priority: Priority::Prevention,
        ));

        $this->assertTrue($result);
    }

    private function createSUT(ReasonRepository $repository, Locale $locale = Locale::Spanish): UpdateReasonService
    {
        FakeLocaleSetter::set($locale);

        return new UpdateReasonService(
            reasonRepository: $repository,
            authorizer: new ReasonAuthorizer(),
        );
    }
}
